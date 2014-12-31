<?php
/**
 * Auteur: Blaise de Carné - blaise@concretis.com
 */

namespace Devyn\Component\RAL\Registry;

use EasyRdf\RdfNamespace;
use EasyRdf\TypeMapper;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class TypeMapperRegistry
 * @package Devyn\Component\RAL\Registry
 */
class TypeMapperRegistry
{
    /**
     * @param $type
     * @param $class
     * @return string
     */
    public function set($type, $class) {
        return TypeMapper::set($type, $class);
    }

    /**
     * @param $type
     * @return string
     */
    public function get($type) {
        return TypeMapper::get($type);
    }

    /**
     * @param $type
     */
    public function delete($type) {
        TypeMapper::delete($type);
    }

    /**
     * @param GetResponseEvent $request
     */
    public function onKernelRequest(GetResponseEvent $request) {
        // empty method to allow kernel request event in service definition
    }
}
