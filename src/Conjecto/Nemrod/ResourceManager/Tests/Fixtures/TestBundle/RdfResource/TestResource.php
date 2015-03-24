<?php
namespace Conjecto\Nemrod\ResourceManager\Tests\Fixtures\TestBundle\RdfResource;

use Conjecto\Nemrod\ResourceManager\Resource\Resource as BaseResource;
use Conjecto\Nemrod\ResourceManager\Annotation\Resource;

/**
 * Class ExampleResource.
 *
 * @Resource(types={"foo:Class"})
 */
class TestResource extends BaseResource
{
}
