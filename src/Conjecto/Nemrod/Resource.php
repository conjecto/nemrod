<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod;

use Conjecto\Nemrod\PropertyAccess\ResourcePropertyAccessor;
use Conjecto\Nemrod\ResourceManager\Mapping\PropertyMetadataAccessor;
use EasyRdf\Literal;
use EasyRdf\Resource as BaseResource;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Class Resource.
 */
class Resource extends BaseResource
{
    /**
     * Is the resource ready for usage within Nemrod ?
     *
     * @var bool
     */
    protected $isReady = false;

    /**
     * Tells if the resource was modified after being loaded.
     *
     * @var bool
     */
    protected $isDirty = false;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     *
     */
    const PROPERTY_PATH_SEPARATOR = '/';

    /**
     * @var Manager
     */
    private $_rm;

    /**
     *
     */
    public function __construct($uri = null, $graph = null)
    {
        $uri = ($uri === null) ? 'e:-1' : $uri;
        $this->propertyAccessor = new ResourcePropertyAccessor();

        return parent::__construct($uri, $graph);
    }

    /** Get all values for a property
     * This method will return an empty array if the property does not exist.
     *
     * @param string $property The name of the property (e.g. foaf:name)
     * @param string $type     The type of value to filter by (e.g. literal)
     * @param string $lang     The language to filter by (e.g. en)
     *
     * @return array An array of values associated with the property
     */
    public function all($property, $type = null, $lang = null)
    {
        list($first, $rest) = $this->split($property);
        $result = parent::all($property, $type, $lang);

        if (is_array($result)) {
            $llResult = array();
            foreach ($result as $res) {
                if ($res instanceof self && (!empty($this->_rm))) {
                    if ($this->_rm->getUnitOfWork()->isManaged($res)) {
                        $llResult[] = $this->_rm->getUnitOfWork()->retrieveResource($res->getUri());
                    }
                    else {
                        $resLazyLoad = $this->_rm->find($res->getUri());
                        $llResult[] = $resLazyLoad ? $resLazyLoad : $res;
                    }
                }
                else if ($res instanceof Literal) {
                    $llResult[] = $res;
                }
            }
            return $llResult;
        }
        else if ($this->_rm->isResource($result)) {
            try {
                if ($result->isBNode()) {
                    $re = $this->_rm->getUnitOfWork()->getPersister()->constructBNode($this->uri, $first);
                }
                else {
                    $re = $this->_rm->find($result->getUri());
                }
                if (!empty($re)) {
                    if ($rest === '') {
                        return $re;
                    }
                    return $re->all($rest, $type, $lang);
                }
                return $re;
            }
            catch (Exception $e) {
                return $e->getMessage();
            }
        }
        else {
            return $result;
        }
    }

    /**
     * @param array|string $property
     * @param null         $type
     * @param null         $lang
     *
     * @return mixed|void
     */
    public function get($property, $type = null, $lang = null)
    {
        list($first, $rest) = $this->split($property);

        //first trying to get first step value
        $result = parent::get($first, $type, $lang);

        if (is_array($result)) {
            if (count($result)) {
                return $result[0];
            }

            return;
        } elseif ($result instanceof self  && (!empty($this->_rm))) { //we get a resource
            try {
                //"lazy load" part : we get the complete resource
                if ($result->isBNode()) {
                    if ($this->_rm->getUnitOfWork()->isManaged($result)) {
                        $re = $this->_rm->getUnitOfWork()->retrieveResource($result->getUri());
                    }
                    else {
                        $re = $this->_rm->getUnitOfWork()->getPersister()->constructBNode($this->uri, $first);
                        $re->setRm($this->_rm);
                    }
                } else {
                    $re = $this->_rm->find($result->getUri());
                }

                if (!empty($re)) {
                    if ($rest === '') {
                        return $re;
                    }
                    //if rest of path is not empty, we get along it
                    return $re->get($rest, $type, $lang);
                }

                return;
            } catch (Exception $e) {
                return;
            }
        } else { //result is a litteral
            return $result;
        }
    }

    /**
     * @return int|void
     */
    public function set($predicate, $value)
    {
        $this->snapshot($predicate);

        //resource: check if managed (for further save
        if ($value instanceof self && (!empty($this->_rm)) && $this->_rm->getUnitOfWork()->isManaged($this)) {
            $this->_rm->persist($value);
        }

        if($property = $this->getMappedProperty($predicate)) {
            $this->propertyAccessor->setValue($this, $property, $value);
        }

        $out = parent::set($predicate, $value);
        return $out;
    }

    /**
     * @return int|void
     */
    public function add($predicate, $value)
    {
        $this->snapshot();

        //resource: check if managed (for further save)
        if ($value instanceof self && (!empty($this->_rm)) && $this->_rm->getUnitOfWork()->isManaged($this)) {
            $this->_rm->persist($value);
        }

        if($property = $this->getMappedProperty($predicate)) {
            if(!$this->propertyAccessor->getValue($this, $property)) {
                $this->propertyAccessor->setValue($this, $property, $value);
            }
        }

        $out = parent::add($predicate, $value);
        return $out;
    }

    /**
     * @return int|void
     */
    public function delete($property, $value = null)
    {
        $this->snapshot();
        $out = parent::delete($property, $value);

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

    /**
     * @param $path
     *
     * @return array
     */
    private function split($path)
    {
        $first = $path;
        $rest = '';
        $firstSep = strpos($path, $this::PROPERTY_PATH_SEPARATOR);

        if ($firstSep) {
            $first = substr($path, 0, $firstSep);
            $rest = substr($path, $firstSep + 1);
        }

        return array($first, $rest);
    }

    /**
     * @param $property
     * @param $value
     */
    private function snapshot()
    {
        if (!$this->isReady) {
            return;
        }

        if (!empty($this->_rm) && !$this->isDirty) {
            $this->_rm->getUnitOfWork()->snapshot($this);
            $this->isDirty = true;
            $this->_rm->getUnitOfWork()->setDirty($this);
        }
    }

    /**
     * Sets the resource as ready for usage within Nemrod.
     */
    public function setReady()
    {
        $this->isReady = true;
    }

    /**
     * Overloading ArrayAccess implementation methods
     * @todo better existence check strategy
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return true;
    }

    /**
     * Overloading ArrayAccess implementation methods
     * @param mixed $offset
     * @return bool
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     *  getMappedProperty
     *
     * @param string $predicate
     * @return string
     */
    private function getMappedProperty($predicate)
    {
        if(method_exists($this, "getRm") && $this->getRm() && $this->getRm()->getMetadataFactory()) {
            $metadata = $this->getRm()->getMetadataFactory()->getMetadataForClass(get_class($this));
            foreach ($metadata->propertyMetadata as $key => $propertyMetadata) {
                if ($propertyMetadata->value == $predicate) {
                    return $key;
                }
            }
        }
        return false;
    }

    /**
     *  getMappedPredicate
     *
     * @param string $predicate
     * @return string
     */
    private function getMappedPredicate($property)
    {
        if($this->getRm()->getMetadataFactory()) {
            $metadata = $this->getRm()->getMetadataFactory()->getMetadataForClass(get_class($this));
            foreach ($metadata->propertyMetadata as $key => $propertyMetadata) {
                if($key == $property) {
                    return $propertyMetadata->value;
                }
            }
        }
        return false;
    }

    /**
     *  Magic method to get the value for a property of a resource, mapped by metadata
     *
     * @param string $name
     * @return string
     */
    public function __get($name)
    {
        if($predicate = $this->getMappedPredicate($name)) {
            $this->get($predicate);
        }
    }

    /**
     *  Magic method to set the value for a property of a resource, mapped by metadata
     *
     * @param string $name
     * @return string
     */
    public function __set($name, $value)
    {
        if($predicate = $this->getMappedPredicate($name)) {
            $this->set($predicate, $value);
        }
        if(property_exists($this, $name)) {
            $this->$name = $value;
        }
    }


}
