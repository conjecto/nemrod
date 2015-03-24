<?php

namespace Conjecto\Nemrod\Framing\Annotation;

/**
 * Serializer JsonLd annotation.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
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
