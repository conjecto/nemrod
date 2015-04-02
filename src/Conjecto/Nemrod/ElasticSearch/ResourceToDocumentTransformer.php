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

use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer;
use Conjecto\Nemrod\Resource;
use EasyRdf\Graph;
use EasyRdf\Resource as BaseResource;
use EasyRdf\Serialiser\JsonLd;
use Elastica\Document;

class ResourceToDocumentTransformer
{
    /**
     * @var SerializerHelper
     */
    protected $serializerHelper;

    /**
     * @var TypeRegistry
     */
    protected $typeRegistry;

    /**
     * @var JsonLdSerializer
     */
    protected $jsonLdSerializer;

    /**
     * @var TypeMapperRegistry
     */
    protected $typeMapperRegistry;

    /**
     * @param SerializerHelper $serializerHelper
     * @param TypeRegistry $typeRegistry
     * @param TypeMapperRegistry $typeMapperRegistry
     * @param JsonLdSerializer $jsonLdSerializer
     */
    public function __construct(SerializerHelper $serializerHelper, TypeRegistry $typeRegistry, TypeMapperRegistry $typeMapperRegistry, JsonLdSerializer $jsonLdSerializer)
    {
        $this->serializerHelper = $serializerHelper;
        $this->typeRegistry = $typeRegistry;
        $this->typeMapperRegistry = $typeMapperRegistry;
        $this->jsonLdSerializer = $jsonLdSerializer;
    }

    /**
     * Transform a resource to an elastica document
     * @param $uri
     * @param $type
     * @return Document|null
     */
    public function transform($uri, $type)
    {
        $index = $this->typeRegistry->getType($type);
        if (!$index) {
            return null;
        }

        $index = $index->getIndex()->getName();
        if ($index && $this->serializerHelper->isTypeIndexed($index, $type)) {
            $graph = $this->serializerHelper->getGraph($index, $uri, $type);
            $jsonLd = $this->jsonLdSerializer->serialize(new BaseResource($uri, $graph), $this->serializerHelper->getTypeFramePath($index, $type));
            $graph = json_decode($jsonLd, true);
            if (!isset($graph['@graph'][0])) {
                return null;
            }
            $json = json_encode($graph['@graph'][0]);
            $json = str_replace('@id', '_id', $json);
            $json = str_replace('@type', '_type', $json);

            return new Document($uri, $json, $type, $index);
        }

        return null;
    }

    /**
     * Transform an elastica document to a resource
     * @param Document $document
     * @return Resource|null
     */
    public function reverseTransform(Document $document)
    {
        if ($document) {
            $uri = $document->getParam('_id');
            $data = $document->getData();
            $data = str_replace('_type', 'rdf:type', $data);
            $data = json_decode($data, true);
            unset($data['_id']);

            $graph = new Graph($uri);
            foreach ($data as $property => $value) {
                if (is_string($value)) {
                    $graph->add($uri, $property, $value);
                }
            }

            $phpClass = $this->typeMapperRegistry->get($data['rdf:type']);
            if ($phpClass) {
                return new $phpClass($uri, $graph);
            }

            return new Resource($uri, $graph);
        }

        return null;
    }
}
