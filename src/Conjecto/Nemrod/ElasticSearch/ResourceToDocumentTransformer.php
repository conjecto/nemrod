<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ElasticSearch;

use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer;
use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
use Conjecto\Nemrod\Resource;
use EasyRdf\Graph;
use EasyRdf\Resource as BaseResource;
use EasyRdf\TypeMapper;
use Elastica\Document;

class ResourceToDocumentTransformer
{
    /**
     * @var SerializerHelper
     */
    protected $serializerHelper;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var JsonLdSerializer
     */
    protected $jsonLdSerializer;

    /**
     * @var TypeMapperRegistry
     */
    protected $typeMapperRegistry;

    /**
     * @param SerializerHelper   $serializerHelper
     * @param TypeRegistry       $typeRegistry
     * @param TypeMapperRegistry $typeMapperRegistry
     * @param JsonLdSerializer   $jsonLdSerializer
     */
    public function __construct(SerializerHelper $serializerHelper, ConfigManager $configManager, TypeMapperRegistry $typeMapperRegistry, JsonLdSerializer $jsonLdSerializer)
    {
        $this->serializerHelper = $serializerHelper;
        $this->configManager = $configManager;
        $this->typeMapperRegistry = $typeMapperRegistry;
        $this->jsonLdSerializer = $jsonLdSerializer;
    }

    /**
     * Transform a resource to an elastica document.
     *
     * @param $uri
     * @param $index
     * @param $type
     *
     * @return Document|null
     */
    public function transform($uris, $index, $type)
    {
        if ($index && $this->serializerHelper->isTypeIndexed($index, $type)) {

            $frame = $this->serializerHelper->getTypeFramePath($index, $type);
            $index = $this->configManager->getIndexConfiguration($index)->getElasticSearchName();
            $docs = array();

            $phpClass = TypeMapper::get($type);
            if (!$phpClass) {
                $phpClass = "EasyRdf\\Resource";
            }

            // determine if ask for single uri
            $single = !is_array($uris);

            // force resource array
            $uris = $single ? array($uris) : $uris;

            // instantiate each resource
            $resources = array();
            foreach($uris as $uri) {
                $resources[] = new $phpClass($uri);
            }

            // if ask for a single resource, add @id to the frame
            if($single && !isset($frame['@id'])) {
                $frame['@id'] = current($uris);
            }

            // serialize
            $jsonLd = $this->jsonLdSerializer->serialize($resources, $frame, array("includeParentClassFrame" => true));
            $graph = json_decode($jsonLd, true);

            // if graph is empty, return;
            if (empty($graph['@graph'])) {
                return;
            }

            // foreach resource
            foreach($graph['@graph'] as $resource) {
                $uri = $resource['@id'];

                $json = json_encode($resource);
                $json = str_replace('@id', '_id', $json);
                $json = str_replace('@type', '_type', $json);

                $docs[] = new Document($uri, $json, $type, $index);
            }

            // if ask for a single resource, return it
            return $single ? current($docs) : $docs;
        }

        return false;
    }

    /**
     * Transform an elastica document to a resource.
     *
     * @param Document $document
     *
     * @return Resource|null
     */
    public function reverseTransform(Document $document)
    {
        if ($document) {
            $uri = $document->getParam('_id');
            $type = $document->getParam('_type');
            $graph = $this->serializerHelper->getGraph($document->getParam('_index'), $uri, $type);

            $phpClass = $this->typeMapperRegistry->get($type);
            if ($phpClass) {
                return new $phpClass($uri, $graph);
            }

            return new Resource($uri, $graph);
        }

        return;
    }
}
