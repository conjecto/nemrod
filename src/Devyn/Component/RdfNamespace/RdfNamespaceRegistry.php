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
        return \EasyRdf_Namespace::namespaces();
    }

    /**
     *
     */
    public function resetNamespaces() {
        \EasyRdf_Namespace::resetNamespaces();
    }

    /**
     * @param $prefix
     * @return string
     */
    public function get($prefix) {
        return \EasyRdf_Namespace::get($prefix);
    }

    /**
     * @param $prefix
     * @param $long
     */
    public function set($prefix, $long) {
        \EasyRdf_Namespace::set($prefix, $long);
    }

    /**
     * @return string
     */
    public function getDefault() {
        return \EasyRdf_Namespace::getDefault();
    }

    /**
     * @param $namespace
     */
    public function setDefault($namespace) {
        \EasyRdf_Namespace::setDefault($namespace);
    }

    /**
     * @param $prefix
     */
    public function delete($prefix) {
        \EasyRdf_Namespace::delete($prefix);
    }

    /**
     *
     */
    public function reset() {
        \EasyRdf_Namespace::reset();
    }

    /**
     * @param $uri
     * @param bool $createNamespace
     * @return array
     */
    public function splitUri($uri, $createNamespace = false) {
        return \EasyRdf_Namespace::splitUri($uri, $createNamespace);
    }

    /**
     * @param $uri
     * @return string
     */
    public function prefixOfUri($uri) {
        return \EasyRdf_Namespace::prefixOfUri($uri);
    }

    /**
     * @param $uri
     * @param bool $createNamespace
     * @return string
     */
    public function shorten($uri, $createNamespace = false) {
        return \EasyRdf_Namespace::shorten($uri, $createNamespace);
    }

    /**
     * @param $shortUri
     * @return string
     */
    public function expand($shortUri) {
        return \EasyRdf_Namespace::expand($shortUri);
    }
}
