<?php
namespace Devyn\Component\RAL\Tests\Manager;

use Devyn\Component\RAL\Manager\UnitOfWork;
use Devyn\Component\RAL\Resource\Resource;
use EasyRdf\Graph;

class UnitOfWorkTest extends ManagerTestCase
{
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

    private function foo()
    {

    }
}