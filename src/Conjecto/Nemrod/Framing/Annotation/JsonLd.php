<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
