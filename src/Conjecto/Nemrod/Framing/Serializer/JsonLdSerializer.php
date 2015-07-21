<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Framing\Serializer;

use Conjecto\Nemrod\Framing\Loader\JsonLdFrameLoader;
use Conjecto\Nemrod\Framing\Provider\GraphProviderInterface;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;
use EasyRdf\Resource;
use EasyRdf\TypeMapper;
use Metadata\MetadataFactory;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class JsonLdSerializer.
 */
class JsonLdSerializer
{
    /**
     * @var RdfNamespaceRegistry
     */
    protected $nsRegistry;

    /**
     * @var JsonLdFrameLoader
     */
    private $loader;

    /**
     * @var GraphProviderInterface
     */
    protected $provider;

    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var string
     */
    protected $frame;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @param JsonLdFrameLoader $loader
     */
    public function __construct(RdfNamespaceRegistry $nsRegistry, JsonLdFrameLoader $loader, GraphProviderInterface $provider, MetadataFactory $metadataFactory)
    {
        $this->nsRegistry = $nsRegistry;
        $this->loader = $loader;
        $this->provider = $provider;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Serialize resource to JsonLD frame
     * @param $resource
     * @param null $frame
     * @param array $options
     * @return array
     */
    public function serialize($resource, $frame = null, $options = array())
    {
        $frame = $frame ? $frame : $this->frame;
        $options = $options ? $options : $this->options;
        $parentClassMetadatas = array();

        // if no frame provided try to find the default one in the resource metadata
        if (!$frame) {
            $parentClassMetadatas = $this->getParentMetadatas($resource, $parentClassMetadatas);
        }

        $frame = $this->getMergedFrames($parentClassMetadatas, $frame);
        $options = $this->getMergedOptions($parentClassMetadatas, $options);

        // if compacting without context, extract it from the frame
        if ($frame && !empty($options['compact']) && empty($options['context']) && isset($frame['@context'])) {
            $options['context'] = $frame['@context'];
        }

        // if the $data is a resource, add the @id in the frame
        if ($resource instanceof Resource && $frame && !isset($frame['@id'])) {
            $frame['@id'] = $resource->getUri();
        }

        // get the graph form the GraphProvider
        $graph = $this->provider->getGraph($resource, $frame);

        $options['frame'] = json_encode($frame, JSON_FORCE_OBJECT);

        return $graph->serialise('jsonld', $options);
    }

    /**
     * Load the final frame composed by parent classes frames and current class frame.
     * @param array $parentClassMetadatas
     * @param array|null $frame
     * @return array|null
     */
    protected function getMergedFrames($parentClassMetadatas = array(), $frame = null)
    {
        $classMetadatas = $parentClassMetadatas;
        if ($frame) {
            $classMetadatas[]['frame'] = $frame;
        }

        // merge resource frame with parent frames
        $finalFrame = array();
        foreach ($classMetadatas as $classMetadata) {
            if ($classMetadata && isset($classMetadata['frame']) && !empty($classMetadata['frame'])) {
                // find frame with frame path and merge included frames
                $frame = $this->mergeWithIncludedFrames($this->loader->load($classMetadata['frame']));
                // merge current frame with other frames
                $finalFrame = array_merge_recursive($finalFrame, $frame);
            }
        }

        // keep the original type
        $types = $finalFrame['@type'];
        if (is_array($types)) {
            $finalFrame['@type'] = $types[0];
        }

        // merge context from namespace registry
        $namespaces = $this->nsRegistry->namespaces();
        if (isset($finalFrame['@context'])) {
            $finalFrame['@context'] = array_merge($finalFrame['@context'], $namespaces);
        } else {
            $finalFrame['@context'] = $namespaces;
        }
        return $finalFrame;
    }

    protected function mergeWithIncludedFrames($frame, $parentKey = null)
    {
        if (!is_array($frame)) {
            return $frame;
        }

        $includedFrame = array();
        foreach ($frame as $key => $subFrame) {
            if ($key === "@include") {
                return $this->includeSubFrame($frame, $subFrame);
            }
            $includedFrame[$key] = $this->mergeWithIncludedFrames($subFrame, $key);
        }

        return $includedFrame;
    }

    protected function includeSubFrame($frame, $subFrame)
    {
        if (is_string($subFrame)) {
            $includedFrame = $this->loader->load($subFrame);
            $frame = array_merge_recursive($includedFrame, $frame);
            unset($frame["@include"]);
        }
        else if (is_array($subFrame) && isset($subFrame['frame']))
        {
            // get frame type and parentObjectFrames option before manipulating the frame
            $initialFrameType = null;
            $parentObjectFrames = null;
            if (isset($frame['@type'])) {
                $initialFrameType = $frame['@type'];
            }
            if (isset($subFrame['parentObjectFrames'])) {
                $parentObjectFrames = $subFrame['parentObjectFrames'];
            }

            // get and merge the included frame
            $includedFrame = $this->loader->load($subFrame['frame']);
            $frame = array_merge_recursive($includedFrame, $frame);

            // clear the frame
            unset($frame["@include"]);
            if ($initialFrameType) {
                $frame["@type"] = $initialFrameType;
            }

            // include subFrame parent frames if parentObjectFrames is setted to true
            if ($parentObjectFrames) {
                $parentClassMetadatas = $this->getParentMetadatas($frame["@type"], array(), true);
                foreach ($parentClassMetadatas as $classMetadata) {
                    if ($classMetadata && isset($classMetadata['frame']) && !empty($classMetadata['frame'])) {
                        $frame = array_merge_recursive($frame, $this->loader->load($classMetadata['frame']));
                    }
                }

                // reset the initial type
                $types = $frame['@type'];
                if (is_array($types)) {
                    $frame['@type'] = $types[0];
                }
            }
        }

        // recall recursive frame include if included frames have other included frames
        return $this->mergeWithIncludedFrames($frame);
    }

    /**
     * Merge all parent classes options with current options.
     * @param array $parentClassMetadatas
     * @param array|null $options
     * @return array|null
     */
    protected function getMergedOptions($parentClassMetadatas = array(), $options = null)
    {
        $classMetadatas = $parentClassMetadatas;
        $classMetadatas[]['options'] = $options;

        // merge resource frame with parent frames
        $finalOptions = array();
        foreach ($classMetadatas as $classMetadata) {
            // merge options
            if (isset($classMetadata['options']) && !empty($classMetadata['options'])) {
                $finalOptions = array_merge_recursive($finalOptions, $classMetadata['options']);
            }
        }

        return $finalOptions;
    }

    /**
     * Search parent classes and fill frame and options for each parent class
     * @param $parentClass
     * @param array $parentClasses
     * @return array
     */
    protected function getParentMetadatas($parentClass, $parentClasses = array(), $skipRoot = false)
    {
        if (!$parentClass) {
            return $parentClasses;
        }

        if (is_string($parentClass)) {
            $metadata = $this->metadataFactory->getMetadataForClass(TypeMapper::get($parentClass));
        }
        else if ($parentClass instanceof \Conjecto\Nemrod\Resource) {
            $metadata = $this->metadataFactory->getMetadataForClass(get_class($parentClass));
        }

        $parentClass = $metadata->getParentClass();
        if (!$skipRoot) {
            $parentClasses[]['frame'] = $metadata->getFrame();
            $parentClasses[]['options'] = $metadata->getOptions();
        }
        $parentClasses = $this->getParentMetadatas($parentClass, $parentClasses);
        return $parentClasses;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        list($controller, $method) = $event->getController();
        $classMetadata = $this->metadataFactory->getMetadataForClass(get_class($controller));
        $methodMetadata = $classMetadata->methodMetadata[$method];

        if ($methodMetadata->frame) {
            $this->frame = $methodMetadata->frame;
        } elseif ($classMetadata->frame) {
            $this->frame = $classMetadata->frame;
        }

        if ($methodMetadata->options) {
            $this->options = $methodMetadata->options;
        } elseif ($classMetadata->options) {
            $this->options = $classMetadata->options;
        }
    }
}
