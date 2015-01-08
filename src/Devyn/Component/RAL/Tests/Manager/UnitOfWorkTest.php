<?php
namespace Devyn\Component\RAL\Tests\Manager;

use Devyn\Component\RAL\Manager\UnitOfWork;
use EasyRdf\Graph;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{

    private $manager;

    private $repoFactory;
    /**
     *
     */
    public function setUp(){
        $this->repoFactory = $this->getMockBuilder('Devyn\Component\RAL\Manager\RepositoryFactory')->setConstructorArgs(array('foo'));
        $this->manager = $this->getMockBuilder('Devyn\Component\RAL\Manager\Manager')->setConstructorArgs(array($this->repoFactory, 'foo'));
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
            'John Doe');

        $uow = new UnitOfWork($this->manager,'fooUrl');
        $uow->update;
        $this->assertEquals(1,1);
    }

    /**
     * mocks a resource
     */
    private function getMockedResource($uri, $graph)
    {

    }

    /**
     * sets graph for a mocked resource
     */
    private function setGraphForMockedResource()
    {

    }

    private function foo()
    {

    }
}