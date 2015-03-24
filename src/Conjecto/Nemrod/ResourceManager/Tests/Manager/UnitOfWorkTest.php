<?php
namespace Conjecto\Nemrod\ResourceManager\Tests\Manager;

use Conjecto\Nemrod\ResourceManager\Manager\UnitOfWork;
use EasyRdf\Graph;

class UnitOfWorkTest extends ManagerTestCase
{
    /**
     *
     */
    public function setUp()
    {
        $this->repoFactory = $this->getMockBuilder('Conjecto\Nemrod\ResourceManager\Manager\RepositoryFactory')->setConstructorArgs(array('foo'));
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

        $uow = new UnitOfWork($this->manager, 'fooUrl');

        $this->assertEquals(1, 1);
    }

    public function testManagerRegisterResource()
    {
        $uow = new UnitOfWork($this->manager, 'fooUrl');
        $res = $this->getMockedResource('FooClass', 'uri:foo:1234', $this->getMockedGraph('FooClass', 'uri:foo:1234'));

        $uow->registerResource($res);
        $this->assertTrue($uow->isRegistered($res));

        $retrieved = $uow->retrieveResource($res->getUri());
        $this->assertEquals($res, $retrieved);
    }

    private function foo()
    {
    }
}
