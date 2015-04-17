<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Tests\Manager;

class ManagerTestCase extends \PHPUnit_Framework_TestCase
{
    protected $manager;

    protected $repoFactory;

    public function setUp()
    {
        $this->repoFactory = $this->getMock('Conjecto\Nemrod\ResourceManager\RepositoryFactory');//->setConstructorArgs(array('foo'))->getMock();
        $this->manager = $this->mockManager();
    }

    /**
     * mocks a resource.
     */
    protected function getMockedResource($className, $uri, $graph)
    {
        $mockedResource = $this/*->getMockBuilder('Resource')
            ->setMethods(array('setRm', 'getUri', 'getGraph'))*/->getMock('Conjecto\Nemrod\Resource', array('setRm', 'getUri', 'getGraph'));
        $mockedResource->method('getUri')->willReturn($uri);
        $mockedResource->method('setRm')->willReturn(null);
        $mockedResource->method('getGraph')->willReturn($graph);

        return $mockedResource;
    }

    /**
     * sets graph for a mocked resource.
     */
    protected function getMockedGraph($class, $uri, $props = array())
    {
        $rdfphpgraph = array($uri => array_merge($props, array('rdf:type' => array(array('type' => 'uri', 'value' => $class)))));
        $mockedGraph = $this->getMock('EasyRdf\Graph');
        $mockedGraph->expects($this->any())->method('toRdfPhp')->willReturn($rdfphpgraph);

        return $mockedGraph;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockManager()
    {
        $metadata = array(
            'Foo\Bar\ResourceClass' => array('type' => 'foo:Type', 'uriPattern' => ''),
            'Foo\Bar\ResourceClass' => array('type' => 'foo:Type', 'uriPattern' => ''),
        );

        return $this
            ->getMockBuilder('Conjecto\Nemrod\Manager')
            ->disableOriginalConstructor()
            //->setConstructorArgs(array($this->repoFactory, 'foo'))
            ->setMethods(array('getEventDispatcher'))
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockUnitOfWork()
    {
        return $this->getMockBuilder('Conjecto\Nemrod\ResourceManager\UnitOfWork')->setConstructorArgs(array($this->manager, 'http://foo.fr'))->getMock();
    }
}
