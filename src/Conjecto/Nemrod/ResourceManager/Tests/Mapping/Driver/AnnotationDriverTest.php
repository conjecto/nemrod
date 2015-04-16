<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Tests\Mapping\Driver;

use Conjecto\Nemrod\ResourceManager\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AnnotationDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAllClassNames()
    {
        AnnotationRegistry::registerFile(__DIR__.'/../../../Annotation/Resource.php');

        $reader = new AnnotationReader();
        $driver = new AnnotationDriver($reader, array('Conjecto\\Nemrod\\ResourceManager\\Tests\\Fixtures\\TestBundle' => __DIR__.'/../../Fixtures/TestBundle/RdfResource'));

        $classes = $driver->getAllClassNames();

        $this->assertEquals(array('Conjecto\Nemrod\ResourceManager\Tests\Fixtures\TestBundle\RdfResource\TestResource'), $classes);
    }
}
