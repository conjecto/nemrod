<?php

namespace Devyn\Component\RAL\Annotation;

/**
 * Annotation class for @RdfResource().
 *
 * @Annotation
 *
 */

class RdfResource
{
    public $uris;

    /**
     * @return array
     */
    public function getUris()
    {
        return $this->uris;
    }

    /**
     * @param array $uris
     */
    public function setUris($uris)
    {
        $this->uris = $uris;
    }
}
