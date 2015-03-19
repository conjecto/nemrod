<?php
namespace Conjecto\RAL\ResourceManager\Tests\Fixtures\TestBundle\RdfResource;

use Conjecto\RAL\ResourceManager\Resource\Resource as BaseResource;
use Conjecto\RAL\ResourceManager\Annotation\Rdf\Resource;

/**
 * Class ExampleResource.
 *
 * @Resource(types={"foo:Class"})
 */
class TestResource extends BaseResource
{
}
