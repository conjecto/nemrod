<?php

namespace Conjecto\RAL\Bundle\RALBundle\Serializer\Annotation;

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
     * @var boolean
     */
    public $compact = true;
}
