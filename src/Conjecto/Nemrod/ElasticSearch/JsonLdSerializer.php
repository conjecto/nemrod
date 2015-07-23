<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 23/07/2015
 * Time: 11:11
 */

namespace Conjecto\Nemrod\ElasticSearch;

use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer as BaseJsonLdSerializer;
use Conjecto\Nemrod\ElasticSearch\JsonLdFrameLoader;
use Conjecto\Nemrod\Framing\Provider\GraphProviderInterface;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;
use Metadata\MetadataFactory;

class JsonLdSerializer extends BaseJsonLdSerializer
{
    /**
     * @var JsonLdFrameLoader
     */
    private $loader;

    /**
     * @var array
     */
    protected $missingProperties;

    public function __construct(RdfNamespaceRegistry $nsRegistry, JsonLdFrameLoader $loader, GraphProviderInterface $provider, MetadataFactory $metadataFactory)
    {
        parent::__construct($nsRegistry, $loader, $provider, $metadataFactory);
    }
}