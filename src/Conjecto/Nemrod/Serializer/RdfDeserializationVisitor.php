<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Serializer;

use JMS\Serializer\AbstractVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\scalar;

/**
 * Class RdfDeserializationVisitor.
 *
 */
class RdfDeserializationVisitor extends AbstractVisitor
{
    protected function decode($str)
    {

    }

    /**
     * Called before the properties of the object are being visited.
     *
     * @param ClassMetadata $metadata
     * @param mixed         $data
     * @param array         $type
     */
    public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        // nothing to do
    }

    /**
     * @param PropertyMetadata $metadata
     * @param mixed            $data
     */
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        // nothing to do
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitNull($data, array $type, Context $context)
    {
        // nothing to do
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitString($data, array $type, Context $context)
    {
        // nothing to do
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitBoolean($data, array $type, Context $context)
    {
        // nothing to do
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDouble($data, array $type, Context $context)
    {
        // nothing to do
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitInteger($data, array $type, Context $context)
    {
        // nothing to do
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitArray($data, array $type, Context $context)
    {
        // nothing to do
    }

    /**
     * Called after all properties of the object have been visited.
     *
     * @param ClassMetadata $metadata
     * @param mixed         $data
     * @param array         $type
     *
     * @return mixed
     */
    public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        // nothing to do
    }

    /**
     * Called before serialization/deserialization starts.
     *
     * @param GraphNavigator $navigator
     */
    public function setNavigator(GraphNavigator $navigator)
    {
        // nothing to do
    }

    /**
     * @deprecated use Context::getNavigator/Context::accept instead
     *
     * @return GraphNavigator
     */
    public function getNavigator()
    {
        // nothing to do
    }

    /**
     * @return object|array|scalar
     */
    public function getResult()
    {
        // nothing to do
    }
}
