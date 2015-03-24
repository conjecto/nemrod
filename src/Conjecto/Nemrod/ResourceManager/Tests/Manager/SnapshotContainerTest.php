<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 22/01/2015
 * Time: 14:05.
 */

namespace Conjecto\Nemrod\ResourceManager\Tests\Manager;

use Conjecto\Nemrod\ResourceManager\SnapshotContainer;

class SnapshotContainerTest extends ManagerTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testSnapshotIsTaken()
    {
        $spContainer = new SnapshotContainer($this->mockUnitOfWork());

        $graph = $this->getMockedGraph('foo:Bar', 'foo:uri:12345', array());
        $res = $this->getMockedResource('Foo\Bar', 'foo:uri:12345', $graph);

        $spContainer->takeSnapshot($res);

        $ro = $spContainer->getSnapshot($res);

        $this->assertEquals($ro->getUri(), $res->getUri());

        $remove = $spContainer->removeSnapshot($res);

        $this->assertTrue($remove);

        $ro = $spContainer->getSnapshot($res);

        $this->assertNull($ro);
    }

    public function testSnapshotIsRemoved()
    {
        $this->assertTrue(true);
    }

    public function testGetSnapshotIs()
    {
        $this->assertTrue(true);
    }
}
