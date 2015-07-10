<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Form\Extension\Core\Type;

use Conjecto\Nemrod\QueryBuilder\Query;
use Conjecto\Nemrod\QueryBuilder;
use Conjecto\Nemrod\QueryBuilder\NemrodQueryBuilderLoader;
use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
use EasyRdf\Exception;
use EasyRdf\Resource;
use Symfony\Component\Form\Exception\StringCastException;
use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A choice list presenting a list of Easy_Rdf_Resource as choices
 * Class ResourceChoiceList.
 */
class ResourceChoiceList extends ObjectChoiceList
{
    /**
     * Whether the resources have already been loaded.
     *
     * @var Boolean
     */
    protected $loaded = false;

    /**
     * The preferred resources.
     *
     * @var array
     */
    protected $preferredResources = array();

    /**
     * @var string
     */
    protected $class;

    /**
     * @var Manager
     */
    protected $rm;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @param array|\Traversable|string $rm
     * @param TypeMapperRegistry        $typeMapperRegistry
     * @param array                     $choices
     * @param null|string               $class
     * @param QueryBuilder|null         $queryBuilder
     * @param null                      $labelPath
     * @param array                     $preferredChoices
     * @param null                      $groupPath
     * @param null                      $valuePath
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct($rm, $choices, $class, $queryBuilder, $labelPath = null, array $preferredChoices = array(), $groupPath = null, $valuePath = null, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->rm = $rm;
        $this->class = $class;
        if ($queryBuilder === null) {
            $this->queryBuilder = $this->rm->getRepository($class)->getQueryBuilder();
        }
        else if ($queryBuilder !== false) {
            $this->queryBuilder = $queryBuilder;
        }
        parent::__construct($choices, $labelPath, $preferredChoices, $groupPath, $valuePath, $propertyAccessor);
    }

    /**
     * Returns the list of resources.
     *
     * @return array
     *
     * @see Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    public function getChoices()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getChoices();
    }

    /**
     * Returns the values for the resources.
     *
     * @return array
     *
     * @see Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    public function getValues()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getValues();
    }

    /**
     * Returns the choice views of the preferred choices as nested array with
     * the choice groups as top-level keys.
     *
     * @return array
     *
     * @see Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    public function getPreferredViews()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getPreferredViews();
    }

    /**
     * Returns the choice views of the choices that are not preferred as nested
     * array with the choice groups as top-level keys.
     *
     * @return array
     *
     * @see Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    public function getRemainingViews()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getRemainingViews();
    }

    /**
     * Returns the resources corresponding to the given values.
     *
     * @param array $values
     *
     * @return array
     *
     * @see Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    public function getChoicesForValues(array $values)
    {
        $resources = array();
        foreach ($values as $uri) {
            if (!empty($uri)) {
                $resources[] = new Resource($uri, null);
            }
        }

        return $resources;
    }

    /**
     * Returns the values corresponding to the given entities.
     *
     * @param array Resource $resources
     *
     * @return array
     *
     * @see Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    public function getValuesForChoices(array $resources)
    {
        $values = array();
        foreach ($resources as $resource) {
            if (!$resource) {
                continue;
            }
            $values[] = $resource->getUri();
        }

        return $values;
    }

    /**
     * Returns the indices corresponding to the given resources.
     *
     * @param array $resources
     *
     * @return array
     *
     * @see Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    public function getIndicesForChoices(array $resources)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getIndicesForChoices($resources);
    }

    /**
     * Returns the resources corresponding to the given values.
     *
     * @param array $values
     *
     * @return array
     *
     * @see Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    public function getIndicesForValues(array $values)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return parent::getIndicesForValues($values);
    }

    /**
     * Creates a new unique index for this entity.
     *
     * If the entity has a single-field identifier, this identifier is used.
     *
     * Otherwise a new integer is generated.
     *
     * @param mixed $entity The choice to create an index for
     *
     * @return int|string A unique index containing only ASCII letters,
     *                    digits and underscores.
     */
    protected function createIndex($resource)
    {
        return $this->fixIndex($resource->getUri());
    }

    /**
     * Creates a new unique value for this entity.
     *
     * If the entity has a single-field identifier, this identifier is used.
     *
     * Otherwise a new integer is generated.
     *
     * @param mixed $entity The choice to create a value for
     *
     * @return int|string A unique value without character limitations.
     */
    protected function createValue($resource)
    {
        return $resource->getUri();
    }

    /**
     * {@inheritdoc}
     */
    protected function fixIndex($index)
    {
        $index = parent::fixIndex($index);
        $index = rtrim(base64_encode($index), '=');

        return $index;
    }

    /**
     * Loads the list with entities from repository.
     *
     * @throws Exception
     * @throws \EasyRdf\Http\Exception
     */
    private function load()
    {
        try {
            if ($this->queryBuilder) {
                $resources = (new NemrodQueryBuilderLoader($this->queryBuilder, $this->rm, $this->class))->getResources(Query::HYDRATE_COLLECTION, ['rdf:type' => $this->class]);

                // The second parameter $labels is ignored by ObjectChoiceList
                if ($resources) {
                    parent::initialize($resources, array(), $this->preferredResources);
                }
            }
        } catch (StringCastException $e) {
            throw new StringCastException(str_replace('argument $labelPath', 'option "property"', $e->getMessage()), null, $e);
        }
        $this->loaded = true;
    }
}
