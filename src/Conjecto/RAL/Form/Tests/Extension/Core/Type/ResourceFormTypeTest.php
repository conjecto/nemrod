<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\RAL\Form\Tests\Extension\Core\Type;

use Symfony\Component\Form\Test\TypeTestCase;
//use Symfony\Component\Form\Tests\Extension\Core\Type\BaseTypeTest;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Tests\Fixtures\Author;
use Symfony\Component\Form\Tests\Fixtures\FixedDataTransformer;
use Symfony\Component\Form\FormError;

class FormTypeTest extends TypeTestCase
{
    protected function getTestedType()
    {
        return 'form.type.resource_form';
    }
}
