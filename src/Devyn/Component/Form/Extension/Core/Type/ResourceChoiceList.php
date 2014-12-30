<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 30/12/2014
 * Time: 11:05
 */

namespace Devyn\Component\Form\Extension\Core\Type;

use Devyn\Component\Form\Extension\Core\DataMapper\ResourcePropertyPathMapper;
use Symfony\Component\Form\Exception\Exception;
use Symfony\Component\Form\Exception\StringCastException;
use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A choice list presenting a list of Easy_Rdf_Resource as choices
 * Class ResourceChoiceList
 * @package Devyn\Component\Form\Extension\Core\Type
 */
class ResourceChoiceList extends ObjectChoiceList
{
    /**
     * Whether the resources have already been loaded.
     *
     * @var Boolean
     */
    private $loaded = false;

    /**
     * The preferred resources.
     *
     * @var array
     */
    private $preferredResources = array();

    /**
     * @param array|\Traversable $choices
     * @param null $labelPath
     * @param array $preferredChoices
     * @param null $groupPath
     * @param null $valuePath
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct($choices, $labelPath = null, array $preferredChoices = array(), $groupPath = null, $valuePath = null, PropertyAccessorInterface $propertyAccessor = null)
    {
        if (!$this->loaded) {
            // Make sure the constraints of the parent constructor are fulfilled
            $resources = array();
        }

        parent::__construct($choices, $labelPath, $preferredChoices, $groupPath, $valuePath, $propertyAccessor);
    }

    /**
     * @throws \EasyRdf_Exception
     * @throws \EasyRdf_Http_Exception
     */
    private function load()
    {
        $resources = [];
        try {
            $foaf = new \EasyRdf_Graph("http://njh.me/foaf.rdf");
            $foaf->load();
            $me = $foaf->primaryTopic();
            $resources = $me->all('foaf:account');

            // The second parameter $labels is ignored by ObjectChoiceList
            parent::initialize($resources, array(), $this->preferredResources);
        } catch (StringCastException $e) {
            throw new StringCastException(str_replace('argument $labelPath', 'option "property"', $e->getMessage()), null, $e);
        }
        $this->loaded = true;
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getChoices();
    }

    /**
     * @return array
     */
    public function getValues()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getValues();
    }

    /**
     * @return array
     */
    public function getPreferredViews()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getPreferredViews();
    }

    /**
     * @return array
     */
    public function getRemainingViews()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getRemainingViews();
    }

    /**
     * @param array $values
     * @return array
     */
    public function getChoicesForValues(array $values)
    {
        $resources = array();
        foreach($values as $uri) {
            if (!empty($uri))
                $resources[] = new \EasyRdf_Resource($uri, null);
        }
        return $resources;
    }

    /**
     * @param array $resources
     * @return array
     */
    public function getValuesForChoices(array $resources)
    {
        $values = array();
        foreach($resources as $resource) {
            if(!$resource) continue;
            $values[] = $resource->getUri();
        }
        return $values;
    }

    /**
     * @param array $resources
     * @return array
     */
    public function getIndicesForChoices(array $resources)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getIndicesForChoices($resources);
    }

    /**
     * @param array $values
     * @return array
     */
    public function getIndicesForValues(array $values)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getIndicesForValues($values);
    }

    /**
     * @param mixed $resource
     * @return int|mixed|string
     */
    protected function createIndex($resource)
    {
        return $this->fixIndex($resource->getUri());
    }

    /**
     * @param mixed $resource
     * @return mixed
     */
    protected function createValue($resource)
    {
        return $resource->getUri();
    }

    /**
     * @param mixed $index
     * @return int|mixed|string
     */
    protected function fixIndex($index)
    {
        $index = parent::fixIndex($index);
        $index = rtrim(base64_encode($index), "=");
        return $index;
    }
}