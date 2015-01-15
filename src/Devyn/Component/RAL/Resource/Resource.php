<?php
namespace Devyn\Component\RAL\Resource;

use Devyn\Component\RAL\Manager\Manager;
use EasyRdf\Graph;
use EasyRdf\Resource as BaseResource;
use Symfony\Component\Config\Definition\Exception\Exception;

class Resource extends BaseResource
{
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

    /**
     *
     * @param array|string $property
     * @param null $type
     * @param null $lang
     * @return mixed|void
     */
    public function get($property, $type = null, $lang = null)
    {

        $pathParts = explode(".",$property);
        $first = $property;
        $rest = "";
        $firstSep = strpos($property, "/");
        //echo $property;
        //echo "length".$firstSep;
        if ($firstSep) {
            $first = substr($property, 0, $firstSep);
            $rest = substr($property, $firstSep+1);
            //echo $first.";".$rest;
        }
        //echo "<br/>";

        $result = parent::get($first, $type, $lang);


        if (is_array($result)) {

        } else if ($this->_rm->isResource($result)) {

                try {
                    if ($result->isBNode()) {
                        $re = $this->_rm->getUnitOfWork()->getPersister()->constructBNode($this->uri, $first);
                    }else {
                        $re = $this->_rm->getUnitOfWork()->getPersister()->constructUri($this->uri, $first);
                    }
                     if (!empty($re)){
                     return $re->get($rest, $type, $lang);
                    }
                    return null;
                } catch (Exception $e) {
                    return null;
                }

        } else {
            //echo "{".$first."|".$result."}";
            return $result;
        }

        return $result;
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
        //echo "set for --->".$this->getUri()."<br/>";
        //var_dump($rm);
        $this->_rm = $rm;
    }

    //public function
} 