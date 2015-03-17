<?php
namespace Conjecto\RAL\Framing\Provider;

use Conjecto\RAL\ResourceManager\Manager\Manager;
use EasyRdf\Graph;
use EasyRdf\Resource;

class ConstructedGraphProvider implements GraphProviderInterface
{
    /**
     * @var Manager
     */
    protected $rm;

    /**
     * Constructor
     *
     * @param $rm
     */
    function __construct($rm)
    {
        $this->rm = $rm;
    }

    /**
     * @param Resource $resource
     * @param array $frame
     * @return Graph
     */
    public function getGraph(Resource $resource, $frame = null)
    {
        $qb = $this->getQueryBuilder($frame);
        $qb->bind("<".$resource->getUri().">", '?uri');
        return $qb->getQuery()->execute();
    }

    /**
     * @param $frame
     */
    protected function getQueryBuilder($frame)
    {
        $qb = clone $this->rm->getQueryBuilder();
        $qb->construct();
        //add the construct part

        foreach ($frame as $prop => $val) {
            if ($prop === '@type') {
                if (is_array($val)) {
                    foreach ($val as $key => $value) {
                        $qb->addConstruct('?uri' . ' a ' . $key);
                        $qb->andWhere('?uri' . ' a ' . $key);
                    }
                }
                else {
                    $qb->addConstruct('?uri' . ' a ' . $val);
                    $qb->andWhere('?uri' . ' a ' . $val);
                }
            }

            // union for optional trick
            if (substr($prop, 0, 1) != '@' && is_array($val)) {
                $uriChild = '?c' . (++$this->varCounter);
                $qb->addConstruct('?uri ' . $prop . ' ' . $uriChild);
                //$properties[] = $prop;
                $qb->addUnion(array("", '?uri' . ' ' . $prop . ' ' . $this->addChild($qb, $val, $uriChild)));
            }

        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $child
     * @param $uriChild
     * @return string
     */
    protected function addChild($qb, $child, $uriChild)
    {
        // no child but in the frame for @explicit
        if (!count($child)) {
            return $uriChild;
        }
        else {
            $stringedChild = $uriChild . ".";
            $hasExplicit = false;

            foreach ($child as $prop => $val) {
                if ($prop === '@type') {
                    $stringedChild = $stringedChild . " " . $uriChild . ' a ' . $val . " .";
                    $qb->addConstruct($uriChild . ' a ' . $val);
                }
                else if ($prop === '@explicit' && $val === 'true') {
                    $hasExplicit = true;
                }
                else if ($prop === '@embed') {

                }
                // @default @omitDefault @null @embed are not usefull
                else{
                    // union for optional trick
                    if (is_array($val)) {
                        $uriChildOfChild = '?c' . (++$this->varCounter);
                        $stringedChild = $stringedChild . " {} UNION {" . $uriChild . ' ' . $prop . ' ' . $this->addChild($qb, $val, $uriChildOfChild) . "} ";
                        $qb->addConstruct($uriChild . ' ' . $prop . ' ' . $this->addChild($qb, $val, $uriChildOfChild));
                    }
                }
            }

            if (!$hasExplicit) {
                $stringedChild = $stringedChild . " " . $uriChild . ' ?w' . (++$this->varCounter) . ' ?w' . (++$this->varCounter) . " .";
            }

            return $stringedChild;
        }
    }

    /**
     * @param $uri
     * @param $type
     * @param $frame
     * @return null|string
     */
    protected function createUnionPart($uri, $type, $frame)
    {
        $rez = "?s a type1found ; prop1/a type1found2 ; prop2 $uri . $uri a $type ";

        // each found must show the path  "?s a type1found ; prop1/a type1found2 ; prop2 $uri . $uri a $type "
        // if not found return null, { path1 } UNION ( path2 }  ... if some found
        // must parse all the frame and contruct by pop and push the request part, when found one add to rez and continue

        $this->onePossiblePlace = [];
        $buildingUnion = '?s a ' . $frame['@type'];

        foreach ($frame as $prop => $val) {
            if ($prop === '@type') {
            }
            else if ($prop === '@explicit' && $val === 'true') {

            }
            else if ($prop === '@embed' ) {

            }
            // @default @omitDefault @null are not usefull, @embed could be
            else {
                // union for optional trick
                if (is_array($val) && count($val)) {
                    $this->checkDeeper($uri, $type, $val, $buildingUnion . "; " . $prop);
                }
            }
        }
        return (count($this->onePossiblePlace))?"{ ".implode(" } UNION { ",$this->onePossiblePlace)." }":null;
    }

    /**
     * @param $uri
     * @param $type
     * @param $frame
     * @param $buildingUnion
     */
    protected function checkDeeper($uri, $type, $frame, $buildingUnion)
    {
        foreach($frame as $prop => $val) {
            if ($prop === '@type') {
                if($val == $type) {
                    $this->onePossiblePlace[] = $buildingUnion . " " . $uri . " .";
                }
            }
            else if ($prop === '@explicit' && $val === 'true') {

            }
            else if ($prop === '@embed' ) {

            }
            // @default @omitDefault @null are not usefull, @embed could be
            else {
                // union for optional trick
                if (is_array($val) && count($val)) {
                    $this->checkDeeper($uri, $type, $val, $buildingUnion . "/" . $prop);
                }
            }
        }
    }
}
