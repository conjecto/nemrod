<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\RAL\Form\Tests\Extension\Core\DataMapper;

use Conjecto\RAL\Form\Extension\Core\DataMapper\ResourcePropertyPathMapper;
use Conjecto\RAL\ResourceManager\Registry\RdfNamespaceRegistry;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;

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
        $this->nsRegistry = $this->getMock('Devyn\Component\RdfNamespace\RdfNamespaceRegistry');
        $this->mapper = new ResourcePropertyPathMapper($this->nsRegistry);
    }

}
