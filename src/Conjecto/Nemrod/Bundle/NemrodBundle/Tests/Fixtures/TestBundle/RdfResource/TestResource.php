<?php
namespace Conjecto\Nemrod\Bundle\NemrodBundle\Tests\Fixtures\TestBundle\RdfResource;

use Conjecto\Nemrod\Resource as BaseResource;
use Conjecto\Nemrod\ResourceManager\Annotation\Rdf\Resource;

/**
 * Class ExampleResource.
 *
 * @Resource(types={"foo:Class"})
 */
class TestResource extends BaseResource
{
}
