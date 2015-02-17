<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 02/12/2014
 * Time: 10:06
 */

namespace Conjecto\EasyRdfBundle\Annotation;

/**
 * @todo
 * Annotation used to declare needed prefixes for a given class
 * @Annotation
 *
 */
class RdfRequiresPrefixes {

    public function __construct($options)
    {
        var_dump($options);
    }
} 