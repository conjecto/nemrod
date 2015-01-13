<?php
namespace Devyn\Component\RAL\Resource;

use Devyn\Component\RAL\Manager\Manager;
use EasyRdf\Graph;
use EasyRdf\Resource as BaseResource;

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
        $result = parent::get($property, $type, $lang);
        //echo $this->getUri();
        //echo "::".$property."::";
        //var_dump($result);
        //var_dump($this);
        //echo 'uu';
        //var_dump($result);//die();
        if (is_array($result)) {

        } else if ($this->_rm->isResource($result)) {
            //echo "00";
            if($result->isBNode())
            {
                //echo 'pw';
                $this->_rm->getUnitOfWork()->getPersister()->constructBNode($this->uri, $property);
                //loading resource
            } else {
                //$this->_rm->find(get_class($this));
            }
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