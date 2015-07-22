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
        $parentClass = null;

        // if no frame provided try to find the default one in the resource metadata
        if (!$frame) {
            $metadata = $this->metadataFactory->getMetadataForClass(get_class($resource));
            $frame = $metadata->getFrame();
            $parentClass = $metadata->getParentClass();
            $parentClassMetadatas = $this->loader->getParentMetadatas($parentClass);
        }

        // load the frame
        $frame = $this->loadFrame($frame, $parentClass);
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
     *
     * @param null $frame
     *
     * @return mixed|null
     */
    protected function loadFrame($frame = null, $parentClass = null)
    {
        // load the frame
        if ($frame) {
            $frame = $this->loader->load($frame, $parentClass);
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
