<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 19/01/2015
 * Time: 12:41
 */

namespace Devyn\Component\QueryBuilder;


use Devyn\Component\RAL\Manager\Manager;
use EasyRdf\Sparql\Result;
use EasyRdf\Graph;

/**
 * Class Query
 * @package Devyn\Component\QueryBuilder
 */
class Query
{
    const STATE_CLEAN = 1;
    const STATE_DIRTY = 2;

    /**
     * The current state of this query.
     *
     * @var integer
     */
    protected $state = self::STATE_DIRTY;

    /**
     * Resource manager for easyrdf resources
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
     * @var integer
     */
    protected $offset = null;

    /**
     * The maximum number of results to return (the "limit").
     *
     * @var integer
     */
    protected $maxResults = null;

    /**
     * Specifies an ordering for the query results
     *
     * @var string
     */
    protected $orderBy = null;

    /**
     * Parser used to verify the sparql query syntaxe
     *
     * @var Parser
     */
    protected $parser;

    /**
     * Sparql query with limit, offset and orderBy
     *
     * @var string
     */
    protected $completeSparqlQuery;

    /**
     * Query result
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
     * @param int $maxResults
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
        $this->state = self::STATE_DIRTY;
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
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        $this->state = self::STATE_DIRTY;
    }

    /**
     * @return mixed
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param mixed $orderBy
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        $this->state = self::STATE_DIRTY;
    }

    /**
     * @return null
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
     * Execute the query
     *
     * @return Graph|Result
     */
    public function execute()
    {
        if ($this->state == self::STATE_DIRTY) {
            $this->completeSparqlQuery = $this->getCompleteSparqlQuery();
            $this->state = self::STATE_CLEAN;
        }

        $this->result = $this->rm->getClient()->query($this->completeSparqlQuery);

        return $this->result;
    }

    /**
     * Complete the query with orderBy, limit and offset
     *
     * @return string
     */
    protected function getCompleteSparqlQuery()
    {
        $sparqlQuery = $this->getSparqlQuery();

        if (!empty($this->orderBy)) {
            $sparqlQuery .= $this->orderBy . ' ';
        }

        if ($this->getOffset() >= 0) {
            $sparqlQuery .= 'OFFSET ' . strval($this->getOffset()) . ' ';
        }

        if ($this->getMaxResults() > 0) {
            $sparqlQuery .= 'LIMIT ' . strval($this->getMaxResults());
        }

        return $sparqlQuery;
    }


    /**
     * Reset the query
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
     * Check if the query has a hint
     *
     * @param  string $name The name of the hint
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
}