<?php
namespace Conjecto\RAL\ResourceManager\Resource;

use Conjecto\RAL\ResourceManager\Manager\Manager;
use EasyRdf\Graph;
use EasyRdf\Resource as BaseResource;
use Symfony\Component\Config\Definition\Exception\Exception;

class Resource extends BaseResource
{
    const PROPERTY_PATH_SEPARATOR = "/";

    /**
     * @var Manager
     */
    private $_rm;

    /**
     *
     */
    public function __construct($uri = null, $graph = null)
    {
        $uri = ($uri == null) ? 'e:-1' : $uri;
        return parent::__construct($uri, $graph);
    }


    /** Get all values for a property
     *
     * This method will return an empty array if the property does not exist.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  string  $type     The type of value to filter by (e.g. literal)
     * @param  string  $lang     The language to filter by (e.g. en)
     *
     * @return array             An array of values associated with the property
     */
    public function all($property, $type = null, $lang = null)
    {
        list($first, $rest) = $this->split($property);

        $result = parent::all($property, $type, $lang);

        if (is_array($result)) {
            //@todo do this better.
            $llResult = array();
            foreach ($result as $res) {

                if ($res instanceof Resource) {
                    $llResult[] = $this->_rm->find(null, $res->getUri());
                } else if ($res instanceof BaseResource) {
                    $nr = new Resource($res->getUri(), $res->getGraph());
                    $nr->setRm($this->_rm);
                    $llResult[] = $nr;
                }
            }

            return $llResult;
        } else if ($this->_rm->isResource($result)) {
            try {
                if ($result->isBNode()) {
                    $re = $this->_rm->getUnitOfWork()->getPersister()->constructBNode($this->uri, $first);
                }else {
                    $re = $this->_rm->find(null, $result->getUri());
                }
                if (!empty($re)){
                    if ($rest == ''){
                        return $re;
                    }
                    return $re->all($rest, $type, $lang);
                }
                return null;
            } catch (Exception $e) {
                return null;
            }

        } else {
            return $result;
        }
    }

    /**
     *
     * @param array|string $property
     * @param null $type
     * @param null $lang
     * @return mixed|void
     */
    public function get($property, $type = null, $lang = null)
    {
        list($first, $rest) = $this->split($property);

        //first trrying to get first step value
        $result = parent::get($first, $type, $lang);

        if (is_array($result)) {
            if (count($result)){
                return $result[0];
            }
            return null;
        } else if ($result instanceof \EasyRdf\Resource) { //we get a resource

            try {
                //"lazy load" part : we get the complete resource
                if ($result->isBNode()) {
                    $re = $this->_rm->getUnitOfWork()->getPersister()->constructBNode($this->uri, $first);
                } else {
                    $re = $this->_rm->find(null, $result->getUri());
                }

                if (!empty($re)){
                     if ($rest == ''){
                         return $re;
                     }
                    //if rest of path is not empty, we get along it
                     return $re->get($rest, $type, $lang);
                }
                return null;
            } catch (Exception $e) {
                return null;
            }

        } else { //result is a litteral
            return $result;
        }
    }

    /**
     * @return int|void
     */
    public function set($a, $b)
    {
        //resource: check if managed (for further save
        if($b instanceof Resource && $this->_rm->getUnitOfWork()->isManaged($this)) {
            $this->_rm->persist($b);
        }
        $out = parent::set($a, $b);

        return $out ;
    }

    public function allResources($path)
    {
        $resources = parent::allResources($path);
        $nr = array();
        foreach ($resources as $rs) {
            $r = new Resource($rs->getUri(), $rs->getGraph());
            $r->setRm($this->_rm);
            $nr[] = $r;
        }
        return $nr;
    }

    /**
     * @return int|void
     */
    public function add($a, $b)
    {
        //resource: check if managed (for further save
        if($b instanceof Resource && $this->_rm->getUnitOfWork()->isManaged($this)) {
            $this->_rm->persist($b);
        }
        $out = parent::add($a, $b);
        return $out;
    }

    /**
     * @return Manager
     */
    public function getRm()
    {
        return $this->_rm;
    }

    /**
     * @param Manager $rm
     */
    public function setRm($rm)
    {
        $this->_rm = $rm;
    }


    private function split($path) {
        $first = $path;
        $rest = "";
        $firstSep = strpos($path, $this::PROPERTY_PATH_SEPARATOR);

        if ($firstSep) {
            $first = substr($path, 0, $firstSep);
            $rest = substr($path, $firstSep+1);
        }
        return array($first, $rest);
    }

} 