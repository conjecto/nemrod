<?php

/*
 * This file is part of the Devyn package.
 *
 * (c) Conjecto
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Devyn\Component\TypeMapper\Annotation;

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
