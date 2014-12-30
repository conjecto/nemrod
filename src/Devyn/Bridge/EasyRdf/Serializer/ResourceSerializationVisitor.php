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

namespace Devyn\Bridge\EasyRdf\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\GenericSerializationVisitor;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\scalar;

class ResourceSerializationVisitor extends GenericSerializationVisitor
{
    /**
     * @var string
    */
    protected $format;

    /**
     * @var \EasyRdf\Graph
     */
    protected $graph;

    /**
     * @param string $format
     */
    public function __construct($format)
    {
        $this->format = $format;
    }

    /**
     * @return object|array|scalar
     */
    public function getResult()
    {
        return $this->graph->serialise($this->format);
    }

    /**
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param array $type
     * @param Context $context
     */
    public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        if($data instanceof \EasyRdf\Graph) {
            $this->graph = $data;
        } elseif($data instanceof \EasyRdf\Resource) {
            $this->graph = $data->getGraph();
        } else {
            throw new InvalidArgumentException('You must provide an EasyRdf Graph or Resource.');
        }
    }

    /**
     * @param PropertyMetadata $metadata
     * @param mixed $data
     * @param Context $context
     * @return void
     */
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        // nothing to do
    }
}
