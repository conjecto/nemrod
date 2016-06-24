<?php
/**
 * Created by PhpStorm.
 * User: Blaise
 * Date: 24/06/2016
 * Time: 16:36
 */

namespace Conjecto\Nemrod\Serializer;


use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer;
use Conjecto\Nemrod\Resource;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;

class JMSResourceHandler implements SubscribingHandlerInterface
{
    /**
     * @var JsonLdSerializer
     */
    protected $jsonLdSerializer;

    /**
     * @param JsonLdSerializer $jsonLdSerializer
     */
    public function __construct(JsonLdSerializer $jsonLdSerializer)
    {
        $this->jsonLdSerializer = $jsonLdSerializer;
    }

    /**
     * @return array
     */
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'Conjecto\\Nemrod\\Resource',
                'method' => 'serializeResourceToJsonLd',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'EasyRdf\Graph',
                'method' => 'serializeResourceToJsonLd',
            ),
        );
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Resource                 $resource
     * @param array                    $type
     * @param SerializationContext     $context
     *
     * @return mixed
     */
    public function serializeResourceToJsonLd(JsonSerializationVisitor $visitor, Resource $resource, array $type, SerializationContext $context)
    {
        $jsonLd = $this->jsonLdSerializer->serialize($resource);
        return $visitor->getNavigator()->accept(json_decode($jsonLd, true), ['name' => 'array'], $context);
    }
}