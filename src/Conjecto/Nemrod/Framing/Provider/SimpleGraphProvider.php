<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Framing\Provider;

use EasyRdf\Graph;
use EasyRdf\Resource;

class SimpleGraphProvider implements GraphProviderInterface
{
    /**
     * @param Resource|Graph $resourceOrGraph
     * @param array    $frame
     *
     * @return Graph
     */
    public function getGraph($resourceOrGraph, $frame = null)
    {
        if ($resourceOrGraph instanceof Graph) {
            return $resourceOrGraph;
        }
        return $resourceOrGraph->getGraph();
    }
}
