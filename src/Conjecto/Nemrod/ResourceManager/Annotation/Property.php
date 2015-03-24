<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property
{
    public function construct($arr)
    {
        parent::__construct($arr);
    }

    public $value;

    public $cascade = array();
}
