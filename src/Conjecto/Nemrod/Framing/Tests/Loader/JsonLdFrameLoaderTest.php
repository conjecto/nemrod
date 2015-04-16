<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Framing\Tests\Loader;

use Conjecto\Nemrod\Framing\Loader\JsonLdFrameLoader;

/**
 * Class JsonLdFrameLoaderTest.
 */
class JsonLdFrameLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @throws \Twig_Error_Loader
     */
    public function testLoad()
    {
        $loader = new JsonLdFrameLoader();
        $loader->addPath(__DIR__.'/Fixtures', 'namespace');

        $expected = array('@id' => 'http://example.org/test#example');

        // Twig-style
        $this->assertEquals($expected, $loader->load('@namespace/frame.jsonld'));
    }
}
