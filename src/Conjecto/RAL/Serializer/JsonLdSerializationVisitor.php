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

namespace Conjecto\RAL\Serializer;

use Conjecto\RAL\Bundle\Loader\JsonLdFrameLoader;
use Conjecto\RAL\ResourceManager\Registry\RdfNamespaceRegistry;
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
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;

/**
 * Class JsonLdSerializationVisitor
 * @package Conjecto\RAL\Serializer
 */
class JsonLdSerializationVisitor extends RdfSerializationVisitor
{
    /**
     * @var RdfNamespaceRegistry
     */
    protected $nsRegistry;
    /**
     * @var JsonLdFrameLoader
     */
    protected $frameLoader;

    /**
     * @param array $options
     */
    public function __construct(RdfNamespaceRegistry $nsRegistry, JsonLdFrameLoader $frameLoader, $options = array())
    {
        $this->nsRegistry = $nsRegistry;
        $this->frameLoader = $frameLoader;
        $this->format = 'jsonld';
        $this->options = $options;
    }

    /**
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param array $type
     * @param Context $context
     */
    public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        parent::startVisitingObject($metadata, $data, $type, $context);
        $this->prepareFraming($metadata, $data, $context);
    }

    /**
     * Get framing options
     *
     * @param ClassMetadata $metadata
     * @param Context $context
     * @return array
     */
    private function getFramingOptions(ClassMetadata $metadata, Context $context) {
        $options = array(
          'frame' => null,
          'compact' => null,
          'context' => null
        );
        foreach(array_keys($options) as $key) {
            // form option
            if(isset($this->options[$key])) {
                $options[$key] = $this->options[$key];
            }

            // from serialization context
            $options[$key] = $context->attributes->get($key)->getOrElse($options[$key]);

            // from class metadata
            if($options[$key] == null && in_array($key, array('frame', 'compact'))) {
                $property = "jsonLd".ucfirst($key);
                if(property_exists($metadata, $property)) {
                    $options[$key] = $metadata->$property;
                }
            }
        }
        return $options;
    }

    /**
     * Prepare the framing
     *
     * @param ClassMetadata $metadata
     * @param Context $context
     */
    private function prepareFraming(ClassMetadata $metadata, $data, Context $context)
    {
        $options = $this->getFramingOptions($metadata, $context);
        $frame = $this->buildFrame($options['frame']);

        // if compacting without context, extract it from the frame
        if($frame && $options['compact'] && !$options['context'] && property_exists($frame, "@context")) {
            $options['context'] = $frame->{"@context"};
        }

        // if the $data is a resource, add the @id in the frame
        if($data instanceof Resource && $frame && !property_exists($frame, "@id")) {
            $frame->{"@id"} = $data->getUri();
        }

        $options['frame'] = $frame;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Build the frame
     *
     * @param null $frame
     * @return mixed|stdClass|null
     */
    private function buildFrame($frame = null)
    {
        // load the frame
        if($frame) {
            $frame = $this->frameLoader->load($frame);
        } else {
            $frame = new \stdClass();
        }

        // merge context from namespace registry
        // @todo limit merge to usefull namespaces
        $namespaces = $this->nsRegistry->namespaces();
        if(property_exists($frame, "@context")) {
            $frame->{"@context"} = (object)array_merge((array)$frame->{"@context"}, $namespaces);
        } else {
            $frame->{"@context"} = (object)$namespaces;
        }

        return $frame;
    }
}
