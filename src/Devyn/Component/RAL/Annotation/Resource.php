<?php

namespace Devyn\Component\RAL\Annotation;

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
}
