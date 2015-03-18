<?php

namespace Conjecto\RAL\Framing\Annotation;

/**
 * Serializer JsonLd annotation
 *
 * @Annotation
 * @Target("CLASS")
 */
class JsonLd
{
    /**
     * @var string
     */
    public $frame = null;

    /**
     * @var array
     */
    public $options = array();
}
