<?php
namespace Devyn\Component\RAL\Tests\Manager;

use Devyn\Component\RAL\Manager\UnitOfWork;
use Devyn\Component\RAL\Resource\Resource;
use EasyRdf\Graph;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{

    private $manager;

    private $repoFactory;
    /**
     *
     */
    public function setUp()
    {
        $this->repoFactory = $this->getMockBuilder('Devyn\Component\RAL\Manager\RepositoryFactory')->setConstructorArgs(array('foo'));
        $this->manager = $this->mockManager();
    }

    public function testMinus()
    {

        $graph1 = new Graph();
        $graph1->add('http://www.example.com/jdoe#jdoe',
            'foaf:name',
            'John Doe');
        $graph2 = new Graph();
        $graph2->add('http://www.example.com/jdoe#jdoe',
            'foaf:name',
            'Foo Bar');

        $uow = new UnitOfWork($this->manager,'fooUrl');
        //$uow->update();
        $this->assertEquals(1,1);
    }

    public function testManagerRegisterResource()
    {
        $uow = new UnitOfWork($this->manager,'fooUrl');
        $res = $this->getMockedResource('FooClass', 'uri:foo:1234', $this->getMockedGraph('FooClass', 'uri:foo:1234'));

        $pouf = $this->getMock('Resource');

        $this->assertInstanceOf('Resource',$pouf);

        $uow->registerResource($res);

        $this->assertTrue($uow->isRegistered($res));
    }

    /**
     * mocks a resource
     */
    private function getMockedResource($className, $uri, $graph)
    {
        $mockedResource = $this/*->getMockBuilder('Resource')
            ->setMethods(array('setRm', 'getUri', 'getGraph'))*/->getMock('Devyn\Component\RAL\Resource\Resource', array('setRm', 'getUri', 'getGraph'));
        $mockedResource->method('getUri')->willReturn($uri);
        $mockedResource->method('setRm')->willReturn(null);
        $mockedResource->method('getGraph')->willReturn($graph);
        ;

        return $mockedResource;
    }

    /**
     * sets graph for a mocked resource
     */
    private function getMockedGraph($class,$uri,$props = array())
    {
        $rdfphpgraph = array ($uri => array ('a' => array(array ('type' => $class, 'value' => 'uri'))));
        $mockedGraph = $this->getMock('EasyRdf\Graph');
        $mockedGraph->expects($this->any())->method("toRdfPhp")->willReturn($rdfphpgraph);

        return $mockedGraph;
    }

    private function mockManager()
    {
        return $this->getMockBuilder('Devyn\Component\RAL\Manager\Manager')->setConstructorArgs(array($this->repoFactory, 'foo'));
    }

    private function foo()
    {

    }
}