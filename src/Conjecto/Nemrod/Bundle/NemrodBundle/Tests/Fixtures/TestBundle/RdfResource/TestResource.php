<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
