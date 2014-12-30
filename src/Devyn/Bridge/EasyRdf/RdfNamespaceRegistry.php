<?php
/**
 * Auteur: Blaise de Carné - blaise@concretis.com
 */

namespace Devyn\Bridge\EasyRdf;

use EasyRdf\RdfNamespace;

/**
 * Class RdfNamespaceRegistry
 * @package Devyn\Bridge\EasyRdf\RdfNamespace
 */
class RdfNamespaceRegistry {

    /**
     * @return array
     */
    public function namespaces() {
        return RdfNamespace::namespaces();
    }

    /**
     *
     */
    public function resetNamespaces() {
        RdfNamespace::resetNamespaces();
    }

    /**
     * @param $prefix
     * @return string
     */
    public function get($prefix) {
        return RdfNamespace::get($prefix);
    }

    /**
     * @param $prefix
     * @param $long
     */
    public function set($prefix, $long) {
        RdfNamespace::set($prefix, $long);
    }

    /**
     * @return string
     */
    public function getDefault() {
        return RdfNamespace::getDefault();
    }

    /**
     * @param $namespace
     */
    public function setDefault($namespace) {
        RdfNamespace::setDefault($namespace);
    }

    /**
     * @param $prefix
     */
    public function delete($prefix) {
        RdfNamespace::delete($prefix);
    }

    /**
     *
     */
    public function reset() {
        RdfNamespace::reset();
    }

    /**
     * @param $uri
     * @param bool $createNamespace
     * @return array
     */
    public function splitUri($uri, $createNamespace = false) {
        return RdfNamespace::splitUri($uri, $createNamespace);
    }

    /**
     * @param $uri
     * @return string
     */
    public function prefixOfUri($uri) {
        return RdfNamespace::prefixOfUri($uri);
    }

    /**
     * @param $uri
     * @param bool $createNamespace
     * @return string
     */
    public function shorten($uri, $createNamespace = false) {
        return RdfNamespace::shorten($uri, $createNamespace);
    }

    /**
     * @param $shortUri
     * @return string
     */
    public function expand($shortUri) {
        return RdfNamespace::expand($shortUri);
    }
}
