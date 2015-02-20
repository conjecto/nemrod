<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 20/02/2015
 * Time: 14:51
 */

namespace Devyn\Component\ESIndexing;


use Devyn\Bridge\Elastica\TypeRegistry;
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

    function __construct(ESCache $esCache, TypeRegistry $typeRegistry)
    {
        $this->esCache = $esCache;
        $this->typeRegistry = $typeRegistry;
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
            $graph = json_decode($jsonLd, true)['@graph'][0];
            $json = json_encode($graph);
            $json = str_replace('@id', '_id', $json);
            $json = str_replace('@type', '_type', $json);

            return new Document($uri, $json, $type, $index);
        }

        return null;
    }

    public function reverseTransform()
    {

    }
}