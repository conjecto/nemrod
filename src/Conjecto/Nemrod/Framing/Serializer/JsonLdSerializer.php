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
     * @param $resource
     * @param $frame
     *
     * @return array
     */
    public function serialize($resource, $frame = null, $parentClass = null, $options = array())
    {
        $frame = $frame ? $frame : $this->frame;
        $options = $options ? $options : $this->options;

        // if no frame provided try to find the default one in the resource metadata
        if (!$frame) {
            $metadata = $this->metadataFactory->getMetadataForClass(get_class($resource));
            $frame = $metadata->getFrame();
            $parentClass = $metadata->getParentClass();
            $options = array_merge($metadata->getOptions(), $options);
        }

        $parentFramePathes = $this->getParentFramesPath($parentClass);
        // load the frame
        $frame = $this->mergeAndLoadFrames($parentFramePathes, $frame);

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

    protected function getParentFramesPath($parentClass)
    {
        if (!$parentClass) {
            return array();
        }

        return array('vcard:Individual' => 'path');
    }

    /**
     * Load the frame.
     *
     * @param null $frame
     *
     * @return mixed|null
     */
    protected function mergeAndLoadFrames($parentFramePathes = array(), $frame = null)
    {
        $framePathes = $parentFramePathes;
        $framePathes[] = $frame;

        // merge resource frame with parent frames
        $finalFrame = array();
        foreach ($framePathes as $frameName) {
            // load the frame
            if ($frameName) {
                $frame = $this->loader->load($frameName);
            } else {
                $frame = array();
            }
            $finalFrame = array_merge_recursive($finalFrame, $frame);
        }

        // keep the original type
        $types = $finalFrame['@type'];
        if (is_array($types)) {
            $finalFrame['@type'] = $types[count($types) - 1];
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
