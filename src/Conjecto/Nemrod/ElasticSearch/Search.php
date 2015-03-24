<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ElasticSearch;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\Filter;
use Elastica\Facet\Terms;
use Elastica\Filter\AbstractFilter;
use Elastica\Filter\Bool;
use Elastica\Filter\BoolAnd;
use Elastica\Filter\BoolOr;
use Elastica\Filter\Range;
use Elastica\Filter\Term;
use Elastica\Query;
use Elastica\SearchableInterface;
use Elastica\Type;
use Elastica\Util;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Search.
 */
class Search
{
    /**
     * @var SearchableInterface
     */
    protected $searchable;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var array
     */
    protected $facets = array();

    /**
     * @var array
     */
    protected $aggregations = array();

    /**
     * @var array
     */
    protected $filters = array();

    /**
     * @var array
     */
    protected $sorts = array();

    /**
     * @param SearchableInterface $searchable
     */
    public function __construct(SearchableInterface $searchable)
    {
        $this->searchable = $searchable;
        $this->query = new Query();
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->getQuery()->setFields($fields);
    }

    /**
     * @param mixed $fields
     */
    public function setSource($fields)
    {
        $this->getQuery()->setSource($fields);
    }

    /**
     * @return array
     */
    public function getFields()
    {
        if ($this->getQuery()->hasParam('fields')) {
            return $this->getQuery()->getParam('fields');
        } else {
            return false;
        }
    }

    /**
     * @param AbstractAggregation $aggregation
     */
    public function addAggregation(AbstractAggregation $aggregation)
    {
        // elasticsearch 1.1 compatibility
        // @see https://github.com/elasticsearch/elasticsearch/issues/5253
        $name = $aggregation->getName();
        $name = preg_replace("/([^a-zA-Z0-9\\-_])/e", "'--'.ord('$1').'--'", $name);
        $aggregation->setName($name);
        // ---

        $this->aggregations[$aggregation->getName()] = $aggregation;
        $this->getQuery()->addAggregation($aggregation);
    }

    /**
     * @param $name
     * @param $field
     *
     * @return Terms
     */
    public function addTermsAggregation($name, $field)
    {
        $aggregation = new \Elastica\Aggregation\Terms($name);
        $aggregation->setField($field);
        $this->addAggregation($aggregation);

        return $aggregation;
    }

    /**
     * @param $name
     */
    public function removeAggregation($name)
    {
        $params = $this->getQuery()->toArray();
        unset($params['aggs'][$name]);
        $this->getQuery()->setRawQuery($params);
        unset($this->aggregations[$name]);
    }

    /**
     * @return array
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param sting          $name
     * @param AbstractFilter $filter
     */
    public function addFilter($name, AbstractFilter $filter)
    {
        $this->filters[$name] = $filter;
        $this->setFilters($this->filters);
    }

    /**
     * @param $name
     * @param $value
     * @param null $field
     *
     * @return Term
     */
    public function addTermFilter($name, $value, $field = null)
    {
        $field = $field ? $field : $name;
        $filter = new Term(array($field => $value));
        $this->addFilter($name, $filter);

        return $filter;
    }

    /**
     * @param $name
     * @param $values
     * @param null $field
     *
     * @return Term
     */
    public function addTermsFilter($name, $values = array(), $field = null)
    {
        $field = $field ? $field : $name;
        $filter = new \Elastica\Filter\Terms($field, $values);
        $this->addFilter($name, $filter);

        return $filter;
    }

    /**
     * @param sting $name
     */
    public function removeFilter($name)
    {
        unset($this->filters[$name]);
        $this->setFilters($this->filters);
    }

    /**
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
        if (count($filters) == 1) {
            $filter = current($filters);
        } else {
            $filter = new Bool();
            foreach ($this->filters as $_filter) {
                $filter->addMust($_filter);
            }
        }
        $this->getQuery()->setPostFilter($filter);
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param AbstractFilter $filter
     */
    public function filterQuery($filter)
    {
        $query = new Query\Filtered(null, $filter);
        if ($this->getQuery()->hasParam("query")) {
            $raw = $this->getQuery()->toArray();
            $query = $query->toArray();
            $query['filtered']['query'] = $raw['query'];
            $raw['query'] = $query;
            $this->getQuery()->setRawQuery($raw);
        } else {
            $this->getQuery()->setQuery($query);
        }
    }

    /**
     * @param $field
     * @param string $order
     * @param array  $options
     */
    public function addSort($field, $order = 'asc', $options = array())
    {
        if ($options) {
            $sort = array($field => array_merge(array('order' => $order), $options));
        } else {
            $sort = array($field => $order);
        }
        $this->sorts[$field] = $sort;
        $this->getQuery()->addSort($sort);
    }

    /**
     * @param $field
     */
    public function removeSort($field)
    {
        $params = $this->getQuery()->toArray();
        foreach ($params['sort'] as $key => $sort) {
            if (current(array_keys($sort)) == $field) {
                unset($params['sort'][$key]);
            }
        }
        $params['sort'] = array_values($params['sort']);
        $this->getQuery()->setRawQuery($params);
        unset($this->sorts[$field]);
    }

    /**
     * @return array
     */
    public function getSorts()
    {
        return $this->sorts;
    }

    /**
     * @param $size
     */
    public function setSize($size = null)
    {
        $page = $this->getPage();
        if ($size) {
            // set the query size
            $this->getQuery()->setSize($size);
        } else {
            // remove the size param from the query
            $params = $this->getQuery()->toArray();
            unset($params['size']);
            $this->getQuery()->setRawQuery($params);
        }
        // reset the page
        $this->setPage($page);
    }

    /**
     * @return int
     */
    public function getSize()
    {
        if ($this->getQuery()->hasParam('size')) {
            return (int) $this->getQuery()->getParam('size');
        } else {
            return 10;
        }
    }

    /**
     * @param $page
     */
    public function setPage($page)
    {
        $size = $this->getSize();
        $from = ($size * ($page - 1));
        if ($from) {
            // set the from param
            $this->getQuery()->setFrom($from);
        } else {
            // remove the from param from the query
            $params = $this->getQuery()->toArray();
            unset($params['from']);
            $this->getQuery()->setRawQuery($params);
        }
    }

    /**
     * @return float|int
     */
    public function getPage()
    {
        if ($this->getQuery()->hasParam('from')) {
            $from = $this->getQuery()->getParam('from');

            return ($from / $this->getSize()) + 1;
        } else {
            return 1;
        }
    }

    /**
     * @param Request $request
     */
    public function handleRequestBody(Request $request)
    {
        $query = $request->request->all();
        $this->query->setRawQuery($this->prepareRawQuery($query));
    }

    /**
     * Prepare a RAW query.
     *
     * @param $query
     */
    private function prepareRawQuery($query)
    {
        foreach ($query as $key => $value) {
            if ($key == 'reverse_nested') {
                // force object for empty reverse_nested
                $query[$key] = (object) $query[$key];
            }
            if (is_array($query[$key])) {
                $query[$key] = $this->prepareRawQuery($query[$key]);
            }
        }

        return $query;
    }

    /**
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        // keywords
        $keywords = $request->get('keywords');
        if ($keywords) {
            $query = new Query\QueryString($keywords);
            $this->query->setQuery($query);
        }

        // fields
        $fields = $request->get('fields');
        if ($fields) {
            $this->setFields($fields);
        }

        // query_filters
        $filters = $request->get('query_filters');
        if ($filters) {
            $bool = new BoolAnd();
            foreach ($filters as $name => $options) {
                $filter = $this->getFilterFromRequest($name, $options);
                $bool->addFilter($filter);
            }
            $this->filterQuery($bool);
        }

        // filters
        $filters = $request->get('filters');
        if ($filters) {
            foreach ($filters as $name => $options) {
                $filter = $this->getFilterFromRequest($name, $options);
                $this->addFilter($name, $filter);
            }
        }

        // aggregations
        $aggregations = $request->get('aggs');
        if ($aggregations) {
            foreach ($aggregations as $name => $options) {
                $this->addAggregationFromRequest($name, $options);
            }
        }

        // sort
        $sorts = $request->get('sorts');
        if ($sorts) {
            foreach ($sorts as $field => $order) {
                $this->addSort($field, $order);
            }
        }

        // size
        $size = $request->get('size');
        if ($size) {
            $this->setSize($size);
        }

        // page
        $page = $request->get('page');
        if ($page) {
            $this->setPage($page);
        }
    }

    /**
     * Add a filter from request.
     *
     * @param $name
     * @param $options
     *
     * @throws \Exception
     *
     * @return AbstractFilter
     */
    protected function getFilterFromRequest($name, $options)
    {
        $instances = is_array($options) && !isset($options['type']) ? $options : array($options);
        $filters = array();

        // foreach filter instance
        foreach ($instances as $options) {

            // SPECIFIC : daterange
            if (is_string($options) && preg_match('/^(\d{2}\/\d{2}\/\d{4}) - (\d{2}\/\d{2}\/\d{4})$/', $options, $matches)) {
                $gte = date_create_from_format("d/m/Y", $matches[1]);
                $lte = date_create_from_format("d/m/Y", $matches[2]);
                $options = array(
                    "type" => "range",
                    "gte" => $gte->format('Y-m-d'),
                    "lte" => $lte->format('Y-m-d'),
                );
            }

            // if the filter dont define type, its a term filter
            if (!is_array($options) || !isset($options['type'])) {
                $options = array(
                    'type' => 'term',
                    $name => $options,
                );
            }

            // find the right filter class
            $camelCase = Util::toCamelCase($options['type']);
            $class = '\Elastica\Filter\\'.$camelCase;
            if (!class_exists($class)) {
                throw new \Exception('Filter class does not exists : '.$class);
            }
            unset($options['type']);

            /* @var AbstractFilter $filter */
            $reflectionObject = new \ReflectionClass($class);
            if (isset($options['args'])) {
                $filter = $reflectionObject->newInstanceArgs($options['args']);
                unset($options['args']);
            } else {
                $filter = $reflectionObject->newInstance();
            }
            if (method_exists($filter, 'addField')) {
                // hack for Range
                $filter->addField($name, $options);
            } else {
                foreach ($options as $key => $value) {
                    $filter->setParam($key, $value);
                }
            }

            $filters[] = $filter;
        }

        if (count($filters) == 1) {
            $filter = reset($filters);
        } else {
            // multiple instances : OR
            $filter = new BoolOr();
            $filter->setFilters($filters);
        }

        return $filter;
    }

    /**
     * Analyze the aggregation options to add the right type.
     *
     * @param $name
     * @param $options
     */
    protected function addAggregationFromRequest($name, $options)
    {
        $type = 'terms';
        if (isset($options['type'])) {
            $type = $options['type'];
            unset($options['type']);
        }

        // find the right aggregation class
        $camelCase = Util::toCamelCase($type);
        $class = '\Elastica\Aggregation\\'.$camelCase;
        if (!class_exists($class)) {
            throw new \Exception('Aggregation class does not exists : '.$class);
        }

        /** @var AbstractAggregation $aggregation */
        $aggregation = new $class($name);
        $aggregation->setField($name);
        foreach ($options as $key => $value) {
            $aggregation->setParam($key, $value);
        }

        $this->addAggregation($aggregation);
    }

    /**
     * Submit the query to the searchable.
     *
     * @return array
     */
    public function search()
    {
        // handle query
        $query = $this->getQuery();
        $resultSet = $this->searchable->search($query);
        $fields = $this->getFields();

        // items
        $items = array();
        foreach ($resultSet->getResults() as $result) {
            $item = $result->getData();
            $item['id'] = $result->getId();
            if (!$fields || in_array('_type', $fields)) {
                $item['_type'] = $result->getType();
            }
            $items[] = $item;
        }

        // build the return
        $return = array(
            'total' => $resultSet->getTotalHits(),
            'pageSize' => $this->getSize(),
            'items' => $items,
        );

        // add the facets
        if ($facets = $resultSet->getFacets()) {
            $return['facets'] = $facets;
        }

        // add the aggregations
        if ($aggregations = $resultSet->getAggregations()) {
            $return['aggs'] = $aggregations;

            // elasticsearch 1.1 compatibility
            // @see https://github.com/elasticsearch/elasticsearch/issues/5253
            $recursive_aggs_rekey = function ($array) use (&$recursive_aggs_rekey) {
                $keys = array_map(function ($key) {
                    return preg_replace('/--(\d+)--/e', "chr('$1')", $key);
                }, array_keys($array));
                $values = array_map(function ($value) use ($recursive_aggs_rekey) {
                    if (is_array($value)) {
                        return $recursive_aggs_rekey($value);
                    } else {
                        return $value;
                    }
                }, array_values($array));

                return array_combine($keys, $values);
            };
            $return['aggs'] = $recursive_aggs_rekey($return['aggs']);
            // ---

            // flatten filtered aggs
            // @see self::filterAggregations
            foreach ($return['aggs'] as $key => $agg) {
                if (isset($agg['doc_count']) && isset($agg[$key])) {
                    $return['aggs'][$key] = $agg[$key];
                }
            }
        }

        return $return;
    }

    /**
     * Submit the query to the searchable.
     *
     * @return array
     */
    public function searchRawResponse()
    {
        // handle query
        $query = $this->getQuery();
        $resultSet = $this->searchable->search($query);

        return $resultSet->getResponse()->getData();
    }
}
