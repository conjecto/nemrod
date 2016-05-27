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
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Conjecto\Nemrod\QueryBuilder;
use Conjecto\Nemrod\QueryBuilder\NemrodQueryBuilderLoader;
use Conjecto\Nemrod\Manager;

/**
 * A choice list presenting a list of Easy_Rdf_Resource as choices
 * Class ResourceChoiceList.
 */
class ResourceChoiceList extends ArrayChoiceList
{
    /**
     * Construct a resource choice list
     * @param array|\Traversable|string $rm
     * @param array                     $choices
     * @param null|string               $class
     * @param QueryBuilder|null         $queryBuilder
     * @param null                      $labelPath
     */
    public function __construct($rm, $choices, $class, $queryBuilder, $labelPath)
    {
        // construct default query builder if empty
        if ($queryBuilder === null) {
            $queryBuilder = $this->getDefaultQueryBuilder($rm, $class, $labelPath);
        }

        if($queryBuilder) {
            if(empty($choices)) {
                $loader = new NemrodQueryBuilderLoader($queryBuilder, $rm, $class);
                $resources = $loader->getResources(Query::HYDRATE_ARRAY, ['rdf:type' => $class]);
            } else {
                $resources = $choices;
            }

            // construct label => value array of choice
            $choices = array();
            foreach ($resources as $resource) {
                $label = $resource->get($labelPath);
                if ($label) {
                    $choices[$resource->get($labelPath)->getValue()] = $resource;
                }
                else {
                    $choices[$resource->getUri()] = $resource;
                }
            }
        }

        // construct choice list
        parent::__construct($choices, function/*getChoiceValue*/($choice) {
            if (!$choice) {
                return null;
            }
            return $choice->getUri();
        });
    }

    /**
     * Construct a query builder to get Uri and label property for a specific class
     * @param Manager $rm
     * @param $class
     * @param $propertyLabel
     * @return mixed
     */
    protected function getDefaultQueryBuilder($rm, $class, $labelPath)
    {
        return $rm->getQueryBuilder()->reset()
            ->construct('?s a ?type')
            ->addConstruct('?s ?propertyLabel ?label')
            ->where('?s a ?type')
            ->andWhere('?s ?propertyLabel ?label')
            ->addOrderBy('?label')
            ->bind($class, '?type')
            ->addBind($labelPath, '?propertyLabel');
    }
}
