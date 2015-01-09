<?php
namespace Devyn\Component\RAL\Resource;

use Devyn\Component\RAL\Manager\Manager;
use EasyRdf\Resource as BaseResource;

class Resource extends BaseResource
{
    /**
     * @var Manager
     */
    private $_rm;

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
        if (is_array($result)) {

        } else if ($this->_rm->isResource($result)) {
            if($result->isBNode())
            {
                //;
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
} 