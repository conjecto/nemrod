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
        $this->loader->setMetadataFactory($metadataFactory);
    }

    /**
     * @return \Conjecto\Nemrod\Framing\Loader\JsonLdFrameLoader
     */
    public function getJsonLdFrameLoader()
    {
        return $this->loader;
    }

    /**
     * @return MetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
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
        $parentClasses = array();

        if (!$frame || !$options || (!empty($options) && isset($options['includeParentClassFrame']) && $options['includeParentClassFrame'] === true)) {
            $metadata = $this->metadataFactory->getMetadataForClass(get_class($resource));
            // if no frame provided try to find the default one in the resource metadata
            if (!$frame) {
                $frame = $metadata->getFrame();
            }

            // if includeParentClassFrame is true, search parentClass with resource SubClassOf annotation
            if (!empty($options) && isset($options['includeParentClassFrame']) && $options['includeParentClassFrame'] === true) {
                $parentClasses = $metadata->getParentClasses();
            }
        }

        // load the frame
        $frame = $this->loadFrame($frame, $parentClasses);

        // load and merge options
        $options = $this->getMergedOptions($this->getParentOptions($parentClasses), $options);

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

    /**
     * Load the frame.
     * @param string|null $frame
     * @param string|null $parentClass
     * @return array
     */
    protected function loadFrame($frame = null, $parentClasses = array())
    {
        // load the frame
        if ($frame) {
            $frame = $this->loader->load($frame, $parentClasses);
        } else {
            $frame = array();
        }

        // merge context from namespace registry
        $namespaces = $this->nsRegistry->namespaces();
        if (isset($frame['@context'])) {
            $frame['@context'] = array_merge($frame['@context'], $namespaces);
        } else {
            $frame['@context'] = $namespaces;
        }

        return $frame;
    }

    /**
     * Search parent class serialization options
     * @param $parentClass
     * @param array $parentOptions
     * @return array
     */
    public function getParentOptions($parentClasses, $parentOptions = array())
    {
        if (!$parentClasses || empty($parentClasses)) {
            return $parentOptions;
        }

        foreach ($parentClasses as $parentClass) {
            $phpClass = TypeMapper::get($parentClass);
            if ($phpClass) {
                $metadata = $this->metadataFactory->getMetadataForClass($phpClass);
                $parentClasses = $metadata->getParentClasses();
                $parentOptions[] = $metadata->getOptions();
                $parentOptions = $this->getParentOptions($parentClasses, $parentOptions);
            }
        }
        return $parentOptions;
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
}
