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
    public function __construct(SerializerHelper $serializerHelper, TypeMapperRegistry $typeMapperRegistry, JsonLdSerializer $jsonLdSerializer)
    {
        $this->serializerHelper = $serializerHelper;
        $this->typeMapperRegistry = $typeMapperRegistry;
        $this->jsonLdSerializer = $jsonLdSerializer;
    }

    /**
     * Transform a resource to an elastica document.
     *
     * @param $uri
     * @param $type
     *
     * @return Document|null
     */
    public function transform($uri, $index, $type)
    {
        if ($index && $this->serializerHelper->isTypeIndexed($index, $type)) {
            $frame = $this->serializerHelper->getTypeFramePath($index, $type);

            $phpClass = TypeMapper::get($type);
            if (!$phpClass) {
                $phpClass = "EasyRdf\\Resource";
            }

            $jsonLd = $this->jsonLdSerializer->serialize(new $phpClass($uri), $frame, array("includeParentClassFrame" => true));
            $graph = json_decode($jsonLd, true);
            if (!isset($graph['@graph'][0])) {
                return;
            }

            $resource = $graph['@graph'][0];
            $resource['@id'] = $uri;

            $json = json_encode($resource);
            $json = str_replace('@id', '_id', $json);
            $json = str_replace('@type', '_type', $json);

            return new Document($uri, $json, $type, $index);
        }

        return;
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
