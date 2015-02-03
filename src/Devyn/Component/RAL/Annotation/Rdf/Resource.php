<?php

namespace Devyn\Component\RAL\Annotation\Rdf;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Resource
{
    /**
     * @var array
     */
    public $types = array();

    public $uriPattern = "";
}
