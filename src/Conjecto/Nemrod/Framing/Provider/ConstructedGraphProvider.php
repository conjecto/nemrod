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

use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\QueryBuilder;
use EasyRdf\Graph;
use EasyRdf\Resource;

class ConstructedGraphProvider extends SimpleGraphProvider
{
    /**
     * @var Manager
     */
    protected $rm;

    public function setRm($rm)
    {
        $this->rm = $rm;
    }

    /**
     * @param \Conjecto\Nemrod\Resource|Graph|array $resourceOrGraph
     * @param array    $frame
     *
     * @return Graph
     */
    public function getGraph($resourceOrGraph, $frame = null)
    {
        $_resource = is_array($resourceOrGraph) ? current($resourceOrGraph) : $resourceOrGraph;

        if ($frame && $this->getProperties($frame)) {
            // if there is a qualified frame, build a new graph to integrate full
            $qb = $this->fillQueryBuilder($frame, $_resource);

            $resources = is_array($resourceOrGraph) ? $resourceOrGraph : array($resourceOrGraph);
            $uris = array();
            foreach($resources as $res) {
                $uris[] = '<'.$res->getUri().'>';
            }
            $qb->value('?uri', $uris);
            $graph = $qb->getQuery()->execute();

            // merge graph to get the last resource values
            $_graph = $_resource instanceof Graph ? $_resource : $_resource->getGraph();
            if($_graph) {
                return $this->mergeGraphs($graph, $_graph);
            } else {
                return $graph;
            }
        } else {
            // else return resource graph
            return parent::getGraph($resourceOrGraph, $frame);
        }
    }

    /**
     * Merge a source graph with a modified graph to handle update
     *
     * @param Graph $source
     * @param Graph $modif
     * @return Graph
     */
    protected function mergeGraphs($source, $modif) {
        $uri = $source->getUri();
        $source = $source->toRdfPhp();
        $modif = $modif->toRdfPhp();

        $merged = [];
        foreach($source as $uri => $properties) {
            if(isset($modif[$uri])) {
                foreach($properties as $prop => $value) {
                    if(!isset($modif[$uri][$prop])) {
                        unset($properties[$prop]);
                    }
                }
                $merged[$uri] = array_merge($properties, $modif[$uri]);
            } else {
                $merged[$uri] = $properties;
            }
        }

        $new = new Graph($uri);
        $new->parse($merged, 'php');
        return $new;
    }

    /**
     * Return properties from frame root.
     *
     * @param array $frame
     *
     * @return array
     */
    protected function getProperties($frame)
    {
        $properties = array();
        foreach ($frame as $key => $val) {
            if (substr($key, 0, 1) !== '@') {
                $properties[] = $key;
            }
        }

        return $properties;
    }

    /**
     * @param $frame
     */
    protected function fillQueryBuilder($frame, $resource)
    {
        $qb = $this->getQueryBuilder($resource);
        $this->fillConstruct($frame, $qb);
        $this->fillWhere($frame, $qb);

        return $qb;
    }

    /**
     * Get resource qb if defined or default manager qb
     * @param $resource
     * @return QueryBuilder
     */
    protected function getQueryBuilder($resource)
    {
        $rm = $this->rm;
        if ($resource instanceof \Conjecto\Nemrod\Resource && $resource->getRm() !== null) {
            $rm = $resource->getRm();
        }
        $qb = clone $rm->getQueryBuilder();
        return $qb->reset();
    }

    /**
     * Add construct parts to qb
     * @param array $frame
     * @param QueryBuilder $qb
     */
    protected function fillConstruct($frame, $qb)
    {
        $this->varCounter = 0;
        $qb->construct();
        $arrayConstruct = array();
        $arrayConstruct = $this->getArrayConstruct($frame, $arrayConstruct, '?uri');
        $this->addConstructParts($arrayConstruct, $qb);
        $this->varCounter = 0;
    }

    /**
     * Add where parts to qb
     * @param array $frame
     * @param QueryBuilder $qb
     */
    protected function fillWhere($frame, $qb)
    {
        $constructUnionWhere = array('');
        foreach ($frame as $prop => $val) {
            if ($prop === '@type') {
                $qb->andWhere("?uri a " . $val);
            }

            // union for optional trick
            if (substr($prop, 0, 1) !== '@' && is_array($val)) {
                $uriChild = '?c' . (++$this->varCounter);
                $constructUnionWhere[] =  "?uri $prop " . $this->addChild($val, $uriChild);
            }
        }
        if(count($constructUnionWhere) > 1){
            $qb->addUnion($constructUnionWhere);
        }
    }

    /**
     * Create an array of construct parts
     * @param $frame
     * @param $arrayConstruct
     * @param $uriParent
     * @return array
     */
    protected function getArrayConstruct($frame, $arrayConstruct, $uriParent)
    {
        foreach ($frame as $prop => $val) {
            if ($prop === '@type') {
                $arrayConstruct[] = "$uriParent a $val";
            } else if (substr($prop, 0, 1) !== '@') {
                $uriChild = '?c'.(++$this->varCounter);
                $arrayConstruct[] = "$uriParent $prop $uriChild";
                if (is_array($val)) {
                    $recursiveConstruct = $this->getArrayConstruct($val, array(), $uriChild);
                    if (count($recursiveConstruct)) {
                        $queryPart = $arrayConstruct[count($arrayConstruct) - 1];
                        $arrayConstruct[count($arrayConstruct) - 1] = array(0 => $queryPart);
                        $arrayConstruct[count($arrayConstruct) - 1][] = $recursiveConstruct;
                    }
                }
            }
        }

        return $arrayConstruct;
    }

    /**
     * Add array construct parts to qb
     * @param array $arrayConstruct
     * @param QueryBuilder $qb
     */
    protected function addConstructParts($arrayConstruct, $qb)
    {
        foreach ($arrayConstruct as $queryPart) {
            if (is_string($queryPart)) {
                $qb->addConstruct($queryPart);
            }
            else if (is_array($queryPart)) {
                $this->addConstructParts($queryPart, $qb);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param $child
     * @param $uriChild
     *
     * @return string
     */
    protected function addChild($child, $uriChild)
    {
        // no child but in the frame for @explicit
        if (!count($child)) {
            return $uriChild;
        } else {
            $stringedChild = $uriChild.'.';
            $typeString = "";
            $firstProperty = ' {}'; // first open solution to simulate optional
            foreach ($child as $prop => $val) {
                if ($prop === '@type') {
                    $typeString = $uriChild . ' a ' . $val.' .';
                }
                // union for optional trick
                if (substr($prop, 0, 1) !== '@' && is_array($val)) {
                    $uriChildOfChild = '?c'.(++$this->varCounter);
                    $stringedChild = $stringedChild.$firstProperty.' UNION {' . $uriChild . ' ' . $prop . ' ' . $this->addChild($val, $uriChildOfChild) . '}';
                    $firstProperty = '';
                }
            }

            return $stringedChild . $typeString;
        }
    }
}
