<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/01/2015
 * Time: 11:22
 */

namespace Devyn\Component\RAL\Mapping;

use \Metadata\PropertyMetadata as BasePropertyMetadata;

class PropertyMetadata extends BasePropertyMetadata
{
    public $value;

    public $cascade;
} 