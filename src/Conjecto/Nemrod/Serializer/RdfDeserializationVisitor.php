<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
 * @TODO
 */
class RdfDeserializationVisitor extends AbstractVisitor
{
    protected function decode($str)
    {
        // TODO: Implement decode() method.
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
