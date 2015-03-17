<?php
namespace Conjecto\RAL\Framing\Provider;

use EasyRdf\Graph;
use EasyRdf\Resource;

/**
 * Interface GraphProviderInterface
 * @package Conjecto\RAL\Framing\Provider
 */
interface GraphProviderInterface
{
    /**
     * @param Resource $resource
     * @param array $frame
     * @return Graph
     */
    public function getGraph(Resource $resource, $frame = null);
}
