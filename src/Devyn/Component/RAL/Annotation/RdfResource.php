<?php

namespace Devyn\Component\RAL\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class RdfResource
{
    /**
     * @var array
     */
    public $uris = array();
}
