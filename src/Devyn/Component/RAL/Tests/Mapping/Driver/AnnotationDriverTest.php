<?php
namespace Devyn\Component\RAL\Tests\Mapping\Driver;

use Devyn\Component\RAL\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AnnotationDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAllClassNames()
    {
        AnnotationRegistry::registerFile('../../../Annotation/Resource.php');

        $reader = new AnnotationReader();
        $driver = new AnnotationDriver($reader, array(__DIR__ . '/../../Fixtures/TestBundle/RdfResource'));

        $classes = $driver->getAllClassNames();

        $this->assertEquals(array('Devyn\Component\RAL\Tests\Fixtures\TestBundle\RdfResource\TestResource'), $classes);
    }
} 