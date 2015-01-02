<?php
/**
 * Auteur: Blaise de Carné - blaise@concretis.com
 */

namespace Devyn\Component\RdfNamespace;

/**
 * Class RdfNamespaceRegistry
 * @package Devyn\Component\RdfNamespace
 */
class RdfNamespaceRegistry {

    /**
     * @return array
     */
    public function namespaces() {
        return \EasyRdf\RdfNamespace::namespaces();
    }

    /**
     *
     */
    public function resetNamespaces() {
        \EasyRdf\RdfNamespace::resetNamespaces();
    }

    /**
     * @param $prefix
     * @return string
     */
    public function get($prefix) {
        return \EasyRdf\RdfNamespace::get($prefix);
    }

    /**
     * @param $prefix
     * @param $long
     */
    public function set($prefix, $long) {
        \EasyRdf\RdfNamespace::set($prefix, $long);
    }

    /**
     * @return string
     */
    public function getDefault() {
        return \EasyRdf\RdfNamespace::getDefault();
    }

    /**
     * @param $namespace
     */
    public function setDefault($namespace) {
        \EasyRdf\RdfNamespace::setDefault($namespace);
    }

    /**
     * @param $prefix
     */
    public function delete($prefix) {
        \EasyRdf\RdfNamespace::delete($prefix);
    }

    /**
     *
     */
    public function reset() {
        \EasyRdf\RdfNamespace::reset();
    }

    /**
     * @param $uri
     * @param bool $createNamespace
     * @return array
     */
    public function splitUri($uri, $createNamespace = false) {
        return \EasyRdf\RdfNamespace::splitUri($uri, $createNamespace);
    }

    /**
     * @param $uri
     * @return string
     */
    public function prefixOfUri($uri) {
        return \EasyRdf\RdfNamespace::prefixOfUri($uri);
    }

    /**
     * @param $uri
     * @param bool $createNamespace
     * @return string
     */
    public function shorten($uri, $createNamespace = false) {
        return \EasyRdf\RdfNamespace::shorten($uri, $createNamespace);
    }

    /**
     * @param $shortUri
     * @return string
     */
    public function expand($shortUri) {
        return \EasyRdf\RdfNamespace::expand($shortUri);
    }
}
