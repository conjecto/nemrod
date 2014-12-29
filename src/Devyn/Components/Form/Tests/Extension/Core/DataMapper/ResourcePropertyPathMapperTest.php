<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Devyn\Component\Form\Tests\Extension\Core\DataMapper;

use Symfony\Component\Form\Extension\Core\DataMapper\ResourcePropertyPathMapper;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;

class ResourcePropertyPathMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourcePropertyPathMapper
     */
    private $mapper;

    protected function setUp()
    {
        $this->mapper = new ResourcePropertyPathMapper();
    }

}
