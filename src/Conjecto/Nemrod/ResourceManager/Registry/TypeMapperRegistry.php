<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Registry;

use EasyRdf\TypeMapper;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class TypeMapperRegistry.
 */
class TypeMapperRegistry
{
    private $rdfClasses = array();

    /**
     * @param $type
     * @param $class
     *
     * @return string
     */
    public function set($type, $class)
    {
        $this->rdfClasses[$class] = $type;

        return TypeMapper::set($type, $class);
    }

    /**
     * @param $class
     *
     * @return string
     */
    public function setDefaultResourceClass($class)
    {
        return TypeMapper::setDefaultResourceClass($class);
    }

    /**
     * @return string
     */
    public function getDefaultResourceClass()
    {
        return TypeMapper::getDefaultResourceClass();
    }

    /**
     * @param $type
     *
     * @return string
     */
    public function get($type)
    {
        return TypeMapper::get($type);
    }

    public function getRdfClass($class)
    {
        if (!isset($this->rdfClasses[$class])) {
            return;
        }

        return $this->rdfClasses[$class];
    }

    /**
     * @param $type
     */
    public function delete($type)
    {
        TypeMapper::delete($type);
    }

    /**
     * @param GetResponseEvent $request
     */
    public function onKernelRequest(GetResponseEvent $request)
    {
        // empty method to allow kernel request event in service definition
    }

    /**
     * Dummy method to be called on commands start.
     */
    public function onConsoleCommand()
    {
    }

    public function getPhpClasses()
    {
        return array_keys($this->rdfClasses);
    }
}
