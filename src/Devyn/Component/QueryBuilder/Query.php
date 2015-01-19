<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 19/01/2015
 * Time: 12:41
 */

namespace Devyn\Component\QueryBuilder;


class Query
{
    /**
     *
     */
    const STATE_CLEAN = 1;
    const STATE_DIRTY = 2;

    /**
     * @var int
     */
    protected $state = self::STATE_DIRTY;

    /**
     * @var
     */
    protected $rm;

    /**
     * @var array
     */
    protected $hints = array();

    /**
     * Cached DQL query.
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
     * @var
     *
     * @var string
     */
    protected $orderBy = null;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $completeSparqlQuery;

    /**
     * @var null
     */
    protected $result;

    public function __construct(/*ResourceManager $rm*/)
    {
//        $this->rm = $rm;
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

    public function execute(/*$hydrationMode = null*/)
    {
        if ($this->state == self::STATE_DIRTY) {
            $this->completeSparqlQuery = $this->getCompleteSparqlQuery();
            $this->state = self::STATE_CLEAN;
        }

        $this->result = $this->rm->getConnection()->query($this->completeSparqlQuery);

        return $this;
    }

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


    public function free()
    {
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