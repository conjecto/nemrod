<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 17/02/2015
 * Time: 14:30
 */

namespace Devyn\Component\ESIndexing;

use Devyn\Component\QueryBuilder\QueryBuilder;
use Devyn\Component\RAL\Manager\Manager;

/**
 * Class ESCache
 * @package Devyn\Component\ESIndexing
 */
class ESCache
{
    /**
     * @var array
     */
    protected $index;

    /**
     * @var array
     */
    protected $requests;

    /**
     * @var Manager
     */
    protected $rm;

    /**
     * @var \Devyn\Component\QueryBuilder\QueryBuilder
     */
    protected $qb;

    /**
     * @var int
     */
    public $varCounter = 0;

    /**
     * @var array
     */
    public $onePossiblePlace = [];

    /**
     * @param Manager $rm
     * @param $index
     * @throws \Exception
     */
    function __construct(Manager $rm, $index)
    {
        $this->rm = $rm;
        $this->index = $index;
        $this->qb = $this->rm->getQueryBuilder();
        $this->guessRequests();
    }

    /**
     * @param $index
     * @param $uri
     * @param $type
     * @param string $property
     * @return mixed
     * @throws \Exception
     */
    public function getRequest($index, $uri, $type)
    {
        if (isset($this->requests[$index][$type]['guessTypeRequest'])) {
            return $this->requests[$index][$type]['guessTypeRequest']->bind("<$uri>", '?uri');
        }
        throw new \Exception('No matching found for index ' . $index . ' and type ' . $type);
    }

    /**
     * @return array
     */
    public function getRequests()
    {
        return $this->requests;
    }

    /**
     * @throws \Exception
     */
    protected function guessRequests()
    {
        foreach ($this->index['indexes'] as $index => $types) {
            foreach ($types['types'] as $type => $settings) {

                if (!isset($settings['type']) || empty($settings['type'])) {
                    throw new \Exception('You have to specify a type for ' . $type);
                }
                if (!isset($settings['frame']) || empty($settings['frame'])) {
                    throw new \Exception('You have to specify a frame for ' . $type);
                }
                if (!isset($settings['properties']) || empty($settings['properties'])) {
                    throw new \Exception('You have to specify properties for ' . $type);
                }
                $this->fillTypeRequests($index, $type, $settings);
            }
        }
    }

    /**
     * @param $index
     * @param $type
     * @param $settings
     * @throws \Exception
     */
    protected function fillTypeRequests($index, $type, $settings)
    {
        $frame = json_decode($settings['frame'], true);
        if (!$frame) {
            throw new \Exception('Invalid frame, the json is not correct');
        }

        $this->requests[$index][$type]['type'] = $settings['type'];
        $this->requests[$index][$type]['context'] = $frame['@context'];
        unset($frame['@context']);
        $this->requests[$index][$type]['frame'] = $frame;
        $this->requests[$index][$type]['guessTypeRequest'] = $this->getTypeRequest($settings['type'], $frame);
    }

    /**
     * @param $type
     * @param $frame
     * @return \Devyn\Component\QueryBuilder\QueryBuilder
     */
    protected function getTypeRequest($type, $frame)
    {
        $qb = clone $this->qb;
        $qb->construct();
        //add the construct part

        $hasExplicit = false;
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
            else if ($prop === '@explicit' && $val === 'true') {
                $hasExplicit = true;
            }
            else if ($prop === '@embed' ) {

            }
            // @default @omitDefault @null @embed are not usefull
            else {
                // union for optional trick
                if (is_array($val)) {
                    $uriChild = '?c' . (++$this->varCounter);
                    $qb->addUnion(array("", '?uri' . ' ' . $prop . ' ' . $this->addChild($qb, $val, $uriChild)));
                }
            }
        }

        if (!$hasExplicit) {
            $qb->andWhere('?uri' . ' ?w' . (++$this->varCounter) . ' ?w' . (++$this->varCounter) . ' .');
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