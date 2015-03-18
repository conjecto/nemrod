<?php

namespace Conjecto\RAL\ResourceManager\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Resource
{
    /**
     * @var array
     */
    public $types = array();

    /**
     * @var string
     */
    public $uriPattern = "";
}
