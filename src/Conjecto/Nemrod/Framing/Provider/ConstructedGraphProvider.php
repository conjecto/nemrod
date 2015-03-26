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
     * @param Resource $resource
     * @param array    $frame
     *
     * @return Graph
     */
    public function getGraph(Resource $resource, $frame = null)
    {
        if ($frame && $this->getProperties($frame)) {
            // if there is a qualified frame, build a new graph to integrate full
            $qb = $this->getQueryBuilder($frame, $resource);
            $qb->bind("<".$resource->getUri().">", '?uri');

            return $qb->getQuery()->execute();
        } else {
            // else return resource graph
            return parent::getGraph($resource, $frame);
        }
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
            if (substr($key, 0, 1) != '@') {
                $properties[] = $key;
            }
        }

        return $properties;
    }

    /**
     * @param $frame
     *
     * @todo use static to cache
     */
    protected function getQueryBuilder($frame, $resource)
    {
        $rm = $this->rm;
        if ($resource instanceof \Conjecto\Nemrod\Resource) {
            $rm = $resource->getRm();
        }

        $qb = clone $rm->getQueryBuilder();
        $qb->construct();
        $properties = array();
        //add the construct part

        foreach ($frame as $prop => $val) {
            if ($prop === '@type') {
                if (is_array($val)) {
                    foreach ($val as $key => $value) {
                        $qb->addConstruct('?uri'.' a '.$key);
                        $qb->andWhere('?uri'.' a '.$key);
                    }
                } else {
                    $qb->addConstruct('?uri'.' a '.$val);
                    $qb->andWhere('?uri'.' a '.$val);
                }
            }

            // union for optional trick
            if (substr($prop, 0, 1) != '@' && is_array($val)) {
                $uriChild = '?c'.(++$this->varCounter);
                $qb->addConstruct('?uri '.$prop.' '.$uriChild);
                $qb->addUnion(array("", '?uri'.' '.$prop.' '.$this->addChild($qb, $val, $uriChild)));
                $properties[] = $prop;
            }
        }

        if (empty($frame['@explicit'])) {
            $triple = '?uri'.' ?w'.(++$this->varCounter).' ?w'.(++$this->varCounter);
            $qb->andWhere($triple);
            $qb->addConstruct($triple);
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $child
     * @param $uriChild
     *
     * @return string
     */
    protected function addChild($qb, $child, $uriChild)
    {
        // no child but in the frame for @explicit
        if (!count($child)) {
            return $uriChild;
        } else {
            $stringedChild = $uriChild.".";

            foreach ($child as $prop => $val) {
                if ($prop === '@type') {
                    $stringedChild = $stringedChild." ".$uriChild.' a '.$val;
                    $qb->addConstruct($uriChild.' a '.$val);
                }
                // union for optional trick
                if (substr($prop, 0, 1) != '@' && is_array($val)) {
                    $uriChildOfChild = '?c'.(++$this->varCounter);
                    $stringedChild = $stringedChild." {} UNION {".$uriChild.' '.$prop.' '.$this->addChild($qb, $val, $uriChildOfChild)."} ";
                    $qb->addConstruct($uriChild.' '.$prop.' '.$this->addChild($qb, $val, $uriChildOfChild));
                }
            }

            if (empty($child['@explicit'])) {
                $triple = $uriChild.' ?w'.(++$this->varCounter).' ?w'.(++$this->varCounter);
                $stringedChild = $stringedChild." . ".$triple;
                $qb->addConstruct($triple);
            }

            return $stringedChild;
        }
    }

    /**
     * @param $uri
     * @param $type
     * @param $frame
     *
     * @return null|string
     */
    protected function createUnionPart($uri, $type, $frame)
    {
        $rez = "?s a type1found ; prop1/a type1found2 ; prop2 $uri . $uri a $type ";

        // each found must show the path  "?s a type1found ; prop1/a type1found2 ; prop2 $uri . $uri a $type "
        // if not found return null, { path1 } UNION ( path2 }  ... if some found
        // must parse all the frame and contruct by pop and push the request part, when found one add to rez and continue

        $this->onePossiblePlace = [];
        $buildingUnion = '?s a '.$frame['@type'];

        foreach ($frame as $prop => $val) {
            // union for optional trick
            if (substr($prop, 0, 1) != '@' && is_array($val) && count($val)) {
                $this->checkDeeper($uri, $type, $val, $buildingUnion."; ".$prop);
            }
        }

        return (count($this->onePossiblePlace)) ? "{ ".implode(" } UNION { ", $this->onePossiblePlace)." }" : null;
    }

    /**
     * @param $uri
     * @param $type
     * @param $frame
     * @param $buildingUnion
     */
    protected function checkDeeper($uri, $type, $frame, $buildingUnion)
    {
        foreach ($frame as $prop => $val) {
            if ($prop === '@type') {
                if ($val == $type) {
                    $this->onePossiblePlace[] = $buildingUnion." ".$uri." .";
                }
            }
            // union for optional trick
            if (substr($prop, 0, 1) != '@' && is_array($val) && count($val)) {
                $this->checkDeeper($uri, $type, $val, $buildingUnion."/".$prop);
            }
        }
    }
}
