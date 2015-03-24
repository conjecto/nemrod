<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Form\Tests\Extension\Core\DataMapper;

use Conjecto\Nemrod\Form\Extension\Core\DataMapper\ResourcePropertyPathMapper;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;

class ResourcePropertyPathMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourcePropertyPathMapper
     */
    private $mapper;

    /**
     * @var RdfNamespaceRegistry
     */
    private $nsRegistry;

    protected function setUp()
    {
        $this->nsRegistry = $this->getMock('Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry');
        $this->mapper = new ResourcePropertyPathMapper($this->nsRegistry);
    }
}
