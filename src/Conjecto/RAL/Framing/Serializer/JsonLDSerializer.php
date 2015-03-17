<?php
namespace Conjecto\RAL\Framing\Serializer;

use Conjecto\RAL\Framing\Loader\JsonLdFrameLoader;
use Conjecto\RAL\Framing\Provider\GraphProviderInterface;
use Conjecto\RAL\ResourceManager\Manager\Manager;
use Conjecto\RAL\ResourceManager\Registry\RdfNamespaceRegistry;
use EasyRdf\Resource;

/**
 * Class JsonLdSerializer
 * @package Conjecto\RAL\Framing\Serializer
 */
class JsonLdSerializer {

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
     * @param JsonLdFrameLoader $loader
     */
    function __construct(RdfNamespaceRegistry $nsRegistry, JsonLdFrameLoader $loader, GraphProviderInterface $provider)
    {
        $this->nsRegistry = $nsRegistry;
        $this->loader = $loader;
        $this->provider = $provider;
    }

    /**
     * @param $resource
     * @param $frame
     * @return array
     */
    public function serialize($resource, $frame = null, $options = array())
    {
        // load the frame
        $frame = $this->loadFrame($frame);

        // if compacting without context, extract it from the frame
        if($frame && !empty($options['compact']) && empty($options['context']) && isset($frame["@context"])) {
            $options['context'] = $frame["@context"];
        }

        // if the $data is a resource, add the @id in the frame
        if($resource instanceof Resource && $frame && !isset($frame["@id"])) {
            $frame["@id"] = $resource->getUri();
        }

        // get the graph form the GraphProvider
        $graph = $this->provider->getGraph($resource, $frame);

        $options['frame'] = json_encode($frame, JSON_FORCE_OBJECT);
        return $graph->serialise("jsonld", $options);
    }


    /**
     * Load the frame
     *
     * @param null $frame
     * @return mixed|null
     */
    protected function loadFrame($frame = null)
    {
        // load the frame
        if($frame) {
            $frame = $this->loader->load($frame);
        } else {
            $frame = array();
        }

        // merge context from namespace registry
        // @todo limit merge to usefull namespaces
        $namespaces = $this->nsRegistry->namespaces();
        if(isset($frame["@context"])) {
            $frame["@context"] = array_merge($frame["@context"], $namespaces);
        } else {
            $frame["@context"] = $namespaces;
        }

        return $frame;
    }

}
