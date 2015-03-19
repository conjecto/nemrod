<?php

namespace Conjecto\RAL\Framing\Serializer;

use Conjecto\RAL\ResourceManager\Registry\RdfNamespaceRegistry;
use EasyRdf\Graph;
use EasyRdf\Resource;
use JMS\Serializer\AbstractVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\GenericSerializationVisitor;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\scalar;

/**
 * Class JsonLdSerializationVisitor
 * @package Conjecto\RAL\Serializer
 */
class JsonLdSerializationVisitor extends AbstractVisitor
{
    /**
     * @var JsonLdSerializer
     */
    protected $serializer;

    /**
     * @var Resource, Graph
     */
    protected $resource;

    /**
     * @var frame
     */
    protected $frame;

    /**
     * @var options
     */
    protected $options;

    /**
     * @param JsonLdSerializer $serializer
     */
    public function __construct(JsonLdSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Called before the properties of the object are being visited.
     *
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param array $type
     *
     * @return void
     */
    public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        if(!($data instanceof Resource) || ($data instanceof Graph)) {
            throw new InvalidArgumentException("JsonLD can only serialize Resource ou Graph object.");
        }
        $this->resource = $data;
        $this->frame = $context->attributes->get('frame')->getOrElse(null);
        $this->options = $context->attributes->get('options')->getOrElse(array());
    }

    /**
     * @return object|array|scalar
     */
    public function getResult()
    {
        return $this->serializer->serialize($this->resource, $this->frame, $this->options);
    }

    /**
     * @param PropertyMetadata $metadata
     * @param mixed $data
     *
     * @return void
     */
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        // TODO: Implement visitProperty() method.
    }

    /**
     * Called after all properties of the object have been visited.
     *
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        // TODO: Implement endVisitingObject() method.
    }

    /**
     * Called before serialization/deserialization starts.
     *
     * @param GraphNavigator $navigator
     *
     * @return void
     */
    public function setNavigator(GraphNavigator $navigator)
    {
        // TODO: Implement setNavigator() method.
    }

    /**
     * @deprecated use Context::getNavigator/Context::accept instead
     * @return GraphNavigator
     */
    public function getNavigator()
    {
        // TODO: Implement getNavigator() method.
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitNull($data, array $type, Context $context)
    {
        // TODO: Implement visitNull() method.
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitString($data, array $type, Context $context)
    {
        // TODO: Implement visitString() method.
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitBoolean($data, array $type, Context $context)
    {
        // TODO: Implement visitBoolean() method.
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDouble($data, array $type, Context $context)
    {
        // TODO: Implement visitDouble() method.
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitInteger($data, array $type, Context $context)
    {
        // TODO: Implement visitInteger() method.
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitArray($data, array $type, Context $context)
    {
        // TODO: Implement visitArray() method.
    }
}