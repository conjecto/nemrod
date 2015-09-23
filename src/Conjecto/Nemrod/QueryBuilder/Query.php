<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\QueryBuilder;

use Conjecto\Nemrod\QueryBuilder;
use Conjecto\Nemrod\QueryBuilder\Internal\Hydratation\AbstractHydrator;
use Conjecto\Nemrod\QueryBuilder\Internal\Hydratation\ArrayHydrator;
use Conjecto\Nemrod\QueryBuilder\Internal\Hydratation\CollectionHydrator;
use Conjecto\Nemrod\Manager;
use EasyRdf\Sparql\Result;
use EasyRdf\Graph;

/**
 * Class Query.
 */
class Query
{
    /**
     * Hydrates graph in an array.
     */
    const HYDRATE_ARRAY = 1;

    /**
     * Hydrates a graph in a collection.
     */
    const HYDRATE_COLLECTION = 2;

    const STATE_CLEAN = 1;
    const STATE_DIRTY = 2;

    /**
     * The current state of this query.
     *
     * @var int
     */
    protected $state = self::STATE_DIRTY;

    /**
     * query type.
     *
     * @var int
     */
    protected $type = QueryBuilder::CONSTRUCT;

    /**
     * Resource manager for easyrdf resources.
     *
     * @var Manager
     */
    protected $rm;

    /**
     * @var array of hints
     */
    protected $hints = array();

    /**
     * Cached sparql query.
     *
     * @var string
     */
    protected $sparqlQuery = null;

    /**
     * The first result to return (the "offset").
     *
     * @var int
     */
    protected $offset = null;

    /**
     * The maximum number of results to return (the "limit").
     *
     * @var int
     */
    protected $maxResults = null;

    /**
     * Specifies an ordering for the query results.
     *
     * @var string
     */
    protected $orderBy = null;

    /**
     * Parser used to verify the sparql query syntaxe.
     *
     * @var Parser
     */
    protected $parser;

    /**
     * Sparql query with limit, offset and orderBy.
     *
     * @var string
     */
    protected $completeSparqlQuery;

    /**
     * Query result.
     *
     * @var Result|Graph
     */
    protected $result;

    /**
     * @param Manager $rm
     */
    public function __construct(Manager $rm)
    {
        $this->rm = $rm;
        $this->parser = new Parser($this);
        $this->result = null;
    }

    /**
     * @return int
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * @param $maxResults
     *
     * @return Query $this
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     *
     * @return Query
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param $orderBy
     *
     * @return Query $this
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return string
     */
    public function getSparqlQuery()
    {
        return $this->sparqlQuery;
    }

    /**
     * @param $sparqlQuery
     *
     * @return $this
     */
    public function setSparqlQuery($sparqlQuery)
    {
        if ($sparqlQuery !== null) {
            $this->sparqlQuery = $sparqlQuery;
            $this->state = self::STATE_DIRTY;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Execute the query.
     *
     * @param null  $hydratation
     * @param array $options
     *
     * @return Graph|Result|null
     */
    public function execute($hydratation = null, $options = array())
    {
        if ($this->state === self::STATE_DIRTY) {
            $this->completeSparqlQuery = $this->getCompleteSparqlQuery();
            $this->state = self::STATE_CLEAN;
        }

        $this->result = $this->rm->getClient()->query($this->completeSparqlQuery);

        if ($this->type === QueryBuilder::SELECT) {
            $this->result = $this->resultToArray($this->result);
        }
        else if ($this->type === QueryBuilder::CONSTRUCT) {
            $this->result = $this->resultToGraph($this->result);
            if (($hydrator = $this->newHydrator($hydratation)) !== null) {
                $this->result = $hydrator->hydrateResources($options);
            }
        }

        return $this->result;
    }

    /**
     * Execute an update query.
     *
     * @return Graph|Result
     */
    public function update($hydratation = null/*self::HYDRATE_ARRAY*/, $options = array())
    {
        if ($this->state === self::STATE_DIRTY) {
            $this->completeSparqlQuery = $this->getCompleteSparqlQuery();
            $this->state = self::STATE_CLEAN;
        }

        $this->result = $this->rm->getClient()->update($this->completeSparqlQuery);

        $this->result = $this->resultToGraph($this->result);

        if (($hydrator = $this->newHydrator($hydratation)) !== null) {
            $this->result = $hydrator->hydrateResources($options);
        }

        return $this->result;
    }

    /**
     * @param $hydratation
     *
     * @return AbstractHydrator|null
     */
    protected function newHydrator($hydratation)
    {
        switch ($hydratation) {
            case self::HYDRATE_COLLECTION:
                return new CollectionHydrator($this);
                break;
            case self::HYDRATE_ARRAY:
                return new ArrayHydrator($this);
                break;
            default:
                return;
                break;
        }
    }

    /**
     * Complete the query with orderBy, limit and offset.
     *
     * @return string
     */
    public function getCompleteSparqlQuery()
    {
        $sparqlQuery = $this->getSparqlQuery();

        if (!empty($this->orderBy)) {
            $sparqlQuery .= $this->orderBy.' ';
        }

        if ($this->type < QueryBuilder::INSERT) {
            if ($this->getOffset() >= 0) {
                $sparqlQuery .= sprintf('OFFSET %s ', strval($this->getOffset()));
            }

            if ($this->getMaxResults() > 0) {
                $sparqlQuery .= sprintf('LIMIT %s', strval($this->getMaxResults()));
            }
        }

        return $sparqlQuery;
    }

    /**
     * Reset the query.
     */
    public function free()
    {
        $this->sparqlQuery = '';
        $this->orderBy = '';
        $this->offset = -1;
        $this->maxResults = 0;
        $this->hints = array();
    }

    /**
     * Sets a query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name  The name of the hint.
     * @param mixed  $value The value of the hint.
     *
     * @return
     */
    public function setHint($name, $value)
    {
        $this->hints[$name] = $value;

        return $this;
    }

    /**
     * Gets the value of a query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @param string $name The name of the hint.
     *
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getHint($name)
    {
        return isset($this->hints[$name]) ? $this->hints[$name] : false;
    }

    /**
     * Check if the query has a hint.
     *
     * @param string $name The name of the hint
     *
     * @return bool False if the query does not have any hint
     */
    public function hasHint($name)
    {
        return isset($this->hints[$name]);
    }

    /**
     * Return the key value map of query hints that are currently set.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }

    /**
     * @return Manager
     */
    public function getRm()
    {
        return $this->rm;
    }

    private function resultToGraph($result)
    {
        if ($result instanceof Graph) {
            return $result;
        }

        $graph = new Graph(null);

        foreach ($result as $row) {
            $graph->add($row->subject, $row->predicate, $row->object);
        }

        return $graph;
    }

    /**
     * @param Result $results
     * @return array
     */
    private function resultToArray($results)
    {
        $arrayResult = array();
        foreach ($results as $key => $result) {
            foreach ($results->getFields() as $field) {
                if ($result->$field instanceof \EasyRdf\Literal) {
                    $arrayResult[$key][$field] = $result->$field->getValue();
                }
                else if ($result->$field instanceof \EasyRdf\Resource) {
                    $arrayResult[$key][$field] = $result->$field->getUri();
                }
                else {
                    $arrayResult[$key][$field] = $result->$field;
                }
            }
        }
        return $arrayResult;
    }
}
