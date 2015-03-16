<?php

use Conjecto\RAL\JsonLDSerializer\Loader\JsonLdFrameLoader;

class JsonLdFrameLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testLoad() {
        $parser = $this->getMock('Symfony\Component\Templating\TemplateNameParserInterface');
        $locator = $this->getMock('Symfony\Component\Config\FileLocatorInterface');
        $locator
          ->expects($this->once())
          ->method('locate')
          ->will($this->returnValue(__DIR__.'/Fixtures/Resources/frames/test.jsonld'))
        ;
        $loader = new JsonLdFrameLoader($locator, $parser);
        $loader->addPath(__DIR__.'/Fixtures/Resources/frames/', 'namespace');

        $expected = array('@id' => "http://example.org/test#example");

        // Twig-style
        $this->assertEquals($expected, $loader->load('@namespace/test.jsonld'));

        // Symfony-style
        $this->assertEquals($expected, $loader->load('TestBundle::test.jsonld'));
    }
}
