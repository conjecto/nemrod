<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Mapping;

use Metadata\PropertyMetadata as BasePropertyMetadata;

class PropertyMetadata extends BasePropertyMetadata
{
    /**
     * Rdf property for php property.
     *
     * @var
     */
    public $value;

    /**
     * Defines cascade behaviors for subresources.
     *
     * @var
     */
    public $cascade;
}
