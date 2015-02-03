<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 26/01/2015
 * Time: 11:55
 */

namespace Devyn\Component\RAL\Annotation\Rdf;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property {

    public function construct($arr)
    {
        parent::__construct($arr);

    }

    public $value;

    public $cascade = array();
} 