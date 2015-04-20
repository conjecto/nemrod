<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Form\Tests\Extension\Core\Type;

use Symfony\Component\Form\Test\TypeTestCase;

class ResourceFormTypeTest extends TypeTestCase
{
    protected function getTestedType()
    {
        return 'form.type.resource_form';
    }

    public function testFoo()
    {
        $this->assertEquals(true, true);
    }
}
