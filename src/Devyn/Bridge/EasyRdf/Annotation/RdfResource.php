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
    private $uris;

    /**
     * Constructor.
     */
    public function __construct($uris)
    {
        if(!is_array($uris)) {
            $uris = array($uris);
        }
        $this->uris = $uris;
    }

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
