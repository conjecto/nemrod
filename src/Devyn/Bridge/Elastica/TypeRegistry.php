<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 18/02/2015
 * Time: 17:15
 */

namespace Devyn\Bridge\Elastica;


/**
 * Class TypeRegistry stores and serves all known elastica types
 * @package Devyn\Bridge\Elastica
 */
class TypeRegistry
{
    private $types = array();

    public function registerType($name, $type)
    {
        $this->types[$name] = $type;
    }

    public function getType($type)
    {
        if (!isset($this->types[$type])) return null;
        return $this->types[$type];
    }
} 