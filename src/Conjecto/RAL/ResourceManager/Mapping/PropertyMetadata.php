<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/01/2015
 * Time: 11:22.
 */

namespace Conjecto\RAL\ResourceManager\Mapping;

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
