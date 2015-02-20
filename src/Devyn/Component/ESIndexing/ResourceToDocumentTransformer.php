<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 20/02/2015
 * Time: 14:51
 */

namespace Devyn\Component\ESIndexing;


use Devyn\Bridge\Elastica\TypeRegistry;
use Devyn\Component\RAL\Registry\TypeMapperRegistry;
use EasyRdf\Graph;
use EasyRdf\Resource;
use EasyRdf\Serialiser\JsonLd;
use Elastica\Document;

class ResourceToDocumentTransformer
{
    /**
     * @var ESCache
     */
    protected $esCache;

    /**
     * @var TypeRegistry
     */
    protected $typeRegistry;

    /**
     * @var TypeMapperRegistry
     */
    protected $typeMapperRegistry;

    function __construct(ESCache $esCache, TypeRegistry $typeRegistry, TypeMapperRegistry $typeMapperRegistry)
    {
        $this->esCache = $esCache;
        $this->typeRegistry = $typeRegistry;
        $this->typeMapperRegistry = $typeMapperRegistry;
    }

    public function transform($uri, $type)
    {
        $qb = $this->esCache->getRm()->getQueryBuilder();
        $index = $this->typeRegistry->getType($type);
        if (!$index) {
            return null;
        }

        $index = $index->getIndex()->getName();
        if ($index && $this->esCache->isTypeIndexed($index, $type)) {
            $jsonLdSerializer = new JsonLd();
            $graph = $this->esCache->getRequest($index, $uri, $type)->getQuery()->execute();
            $jsonLd = $jsonLdSerializer->serialise($graph, 'jsonld', ['context' => $this->esCache->getTypeContext($index, $type), 'frame' => $this->esCache->getTypeFrame($index, $type)]);
            $graph = json_decode($jsonLd, true);
            if (!isset($graph['@graph'][0])) {
                return null;
            }
            $json = json_encode($graph);
            $json = str_replace('@id', '_id', $json);
            $json = str_replace('@type', '_type', $json);

            return new Document($uri, $json, $type, $index);
        }

        return null;
    }

    public function reverseTransform(Document $document)
    {
        if ($document) {
            $uri = $document->getParam('_id');
            $data = $document->getData();
            $data = json_decode($data, true);

            if (!isset($data['@graph'][0])) {
                return null;
            }

            $data = json_encode($data['@graph'][0]);
            $data = str_replace('_type', 'rdf:type', $data);
            $data = json_decode($data, true);
            unset($data['_id']);

            $graph = new Graph($uri);
            foreach ($data as $property => $value) {
                $graph->add($uri, $property, $value);
            }
            $phpClass = $this->typeMapperRegistry->get($data['rdf:type']);
            if ($phpClass) {
                $res = new $phpClass($uri, $graph);
                return $res;
            }
            return new \Devyn\Component\RAL\Resource\Resource($uri, $graph);
        }

        return null;
    }
}