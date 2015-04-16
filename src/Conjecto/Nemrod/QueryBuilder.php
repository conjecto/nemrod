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

use Conjecto\Nemrod\QueryBuilder\Query;
use Doctrine\ORM\Query\Expr\GroupBy;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class QueryBuilder.
 */
class QueryBuilder
{
    /* The query types. */
    const CONSTRUCT = 0;
    const DESCRIBE  = 1;
    const SELECT    = 2;
    const ASK       = 3;
    const INSERT    = 4;
    const DELETE    = 5;
    const DELETE_INSERT = 6;

    /* The builder states. */
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /**
     * query type.
     *
     * @var int
     */
    protected $type = self::CONSTRUCT;

    /**
     * The state of the query object. Can be dirty or clean.
     *
     * @var int
     */
    protected $state = self::STATE_CLEAN;

    /**
     * array if multiple, null if only one with multiples parts.
     *
     * @var array
     */
    protected $sparqlParts = array(
        'construct'  => array(),
        'describe'  => array(),
        'select'  => array(),
        'ask'  => array(),
        'insert'  => array(),
        'delete'  => array(),
        'where'   => array(),
        'optional' => array(),
        'filter' => array(),
        'bind' => array(),
        'value' => array(),
        'orderBy' => array(),
        'groupBy' => array(),
        'distinct' => false,
    );

    /**
     * result size limit.
     *
     * @var int
     */
    protected $maxResults;

    /**
     * query offset.
     *
     * @var int
     */
    protected $offset;

    /**
     * sparql query as string.
     *
     * @var string
     */
    protected $sparqlQuery;

    /**
     * Resource Manager for easyrdf resources.
     *
     * @var Manager
     */
    protected $rm;

    /**
     * Initializes a new QueryBuilder that uses the given RdfNamespaceRegistry.
     *
     * @param string      $endpointUri
     * @param string|null $updatedEndPointUri
     */
    public function __construct(Manager $rm = null)
    {
        $this->rm = $rm;
        $this->maxResults = 0;
        $this->offset = -1;
    }

    /**
     * Specifies triplet for construct query
     * Replaces any previously specified construct, if any.
     *
     * @param null $construct
     *
     * @return $this|QueryBuilder
     */
    public function construct($construct = null)
    {
        return $this->addConstructToQuery($construct, false);
    }

    /**
     * Adds an triplet to construst query.
     *
     * @param null $construct
     *
     * @return $this|QueryBuilder
     */
    public function addConstruct($construct)
    {
        return $this->addConstructToQuery($construct, true);
    }

    /**
     * Specifies objects for select query
     * Replaces any previously specified construct, if any.
     *
     * @param null $select
     *
     * @return $this|QueryBuilder
     */
    public function select($select)
    {
        return $this->addSelectToQuery($select, false);
    }

    /**
     * Shortcut to add a select('*') to the query.
     *
     * @return $this|QueryBuilder
     */
    public function selectAll()
    {
        return $this->addSelectToQuery('*', false);
    }

    /**
     * Adds an triplet to select query.
     *
     * @param null $select
     *
     * @return $this|QueryBuilder
     */
    public function addSelect($select)
    {
        return $this->addSelectToQuery($select, true);
    }

    /**
     * Specifies query type to ask.
     *
     * @param null $ask
     *
     * @return $this|QueryBuilder
     */
    public function ask($ask = null)
    {
        return $this->addAskToQuery($ask, false);
    }

    public function addAsk($ask)
    {
        return $this->addAskToQuery($ask, true);
    }

    /**
     * Specifies object for describe query
     * Replaces any previously specified describe, if any.
     *
     * @param $describe
     *
     * @return $this|QueryBuilder
     */
    public function describe($describe)
    {
        return $this->addDescribeToQuery($describe, false);
    }

    /**
     * Adds an object to describe query.
     *
     * @param null $select
     *
     * @return $this|QueryBuilder
     */
    public function addDescribe($select)
    {
        return $this->addDescribeToQuery($select, true);
    }

    /**
     * Specifies triplet for insert query
     * Replaces any previously specified insert, if any.
     *
     * @param null $insert
     *
     * @return $this|QueryBuilder
     */
    public function insert($insert)
    {
        return $this->addInsertToQuery($insert, false);
    }

    /**
     * Adds an object to insert query.
     *
     * @param null $insert
     *
     * @return $this|QueryBuilder
     */
    public function addInsert($insert)
    {
        return $this->addInsertToQuery($insert, true);
    }

    /**
     * Specifies triplet for delete query
     * Replaces any previously specified insert, if any.
     *
     * @param $delete
     *
     * @return $this|QueryBuilder
     */
    public function delete($delete = null)
    {
        return $this->addDeleteToQuery($delete, false);
    }

    /**
     * Adds an object to delete query.
     *
     * @param null $delete
     *
     * @return QueryBuilder
     */
    public function addDelete($delete)
    {
        return $this->addDeleteToQuery($delete, true);
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * @param $where
     *
     * @return QueryBuilder
     */
    public function where($where)
    {
        return $this->addWhereToQuery($where, false);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * @param $where
     *
     * @return QueryBuilder
     */
    public function andWhere($where)
    {
        return $this->addWhereToQuery($where, true);
    }

    /**
     * Specifies optional for the query
     * Replaces any previously specified optionals, if any.
     *
     * @param $optional
     *
     * @return QueryBuilder
     */
    public function optional($optional)
    {
        return $this->addOptionalToQuery($optional, false);
    }

    /**
     * Adds an optional to the query.
     *
     * @param $optional
     *
     * @return QueryBuilder
     */
    public function addOptional($optional)
    {
        return $this->addOptionalToQuery($optional, true);
    }

    /**
     * Specifies filter for the query
     * Replaces any previously specified filters, if any.
     *
     * @param $filter
     *
     * @return QueryBuilder
     */
    public function filter($filter)
    {
        return $this->addFilterToQuery($filter, false);
    }

    /**
     * Adds a filter to the query.
     *
     * @param $filter
     *
     * @return QueryBuilder
     */
    public function addFilter($filter)
    {
        return $this->addFilterToQuery($filter, true);
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param $sort
     * @param string $order
     *
     * @return QueryBuilder
     */
    public function orderBy($sort, $order = 'ASC')
    {
        return $this->addOrderByToQuery($sort, $order, false);
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param $sort
     * @param null $order
     *
     * @return QueryBuilder
     */
    public function addOrderBy($sort, $order = null)
    {
        return $this->addOrderByToQuery($sort, $order, true);
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * @param $groupBy
     *
     * @return QueryBuilder
     */
    public function groupBy($groupBy)
    {
        return $this->addGroupByToQuery($groupBy, false);
    }

    /**
     * Adds a grouping expression to the query.
     *
     * @param $groupBy
     *
     * @return QueryBuilder
     */
    public function addGroupBy($groupBy)
    {
        return $this->addGroupByToQuery($groupBy, true);
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * @param $value
     * @param null $key
     *
     * @return QueryBuilder
     */
    public function bind($value, $key = null)
    {
        return $this->addBindToQuery($value, $key, false);
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * @param string      $value
     * @param string|null $key
     *
     * @return QueryBuilder
     */
    public function addBind($value, $key = null)
    {
        return $this->addBindToQuery($value, $key, true);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * @param array $arrayPredicates
     *
     * @return QueryBuilder
     */
    public function union($arrayPredicates)
    {
        return $this->addUnionToQuery($arrayPredicates, false);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * @param array $arrayPredicates
     *
     * @return QueryBuilder
     */
    public function addUnion($arrayPredicates)
    {
        return $this->addUnionToQuery($arrayPredicates, true);
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param int $maxResults The maximum number of results to retrieve.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * Set distinct value.
     *
     * @param bool $distinct
     */
    public function setDistinct($distinct)
    {
        $this->sparqlParts['distinct'] = $distinct;

        return $this;
    }

    /**
     * Sets the offset number of results to retrieve (the "offset").
     *
     * @param int $offset
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    public function value($key, $value)
    {
        return $this->addValuesToQuery($key, $value, false);
    }

    public function addValue($key, $value)
    {
        return $this->addValuesToQuery($key, $value, true);
    }

    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->getReducedSparqlQueryPart('orderBy', array('pre' => 'ORDER BY ', 'separator' => ' ', 'post' => ''));
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        if (!$this->rm) {
            throw new \InvalidArgumentException('You must provide a Manager to execute query.');
        }

        return $this->rm->createQuery($this->getSparqlQuery())
            ->setType($this->type)
            ->setOffset($this->offset)
            ->setMaxResults($this->maxResults)
            ->setOrderBy($this->getOrderBy());
    }

    /**
     * Gets the complete DQL string formed by the current specifications of this QueryBuilder.
     *
     * @return string
     */
    public function getSparqlQuery()
    {
        if ($this->sparqlQuery !== null && $this->state === self::STATE_CLEAN) {
            return $this->sparqlQuery;
        }

        switch ($this->type) {
            case self::CONSTRUCT:
                $sparqlQuery = $this->getSparqlQueryForConstruct();
                break;
            case self::SELECT:
                $sparqlQuery = $this->getSparqlQueryForSelect();
                break;
            case self::ASK:
                $sparqlQuery = $this->getSparqlQueryForAsk();
                break;
            case self::DESCRIBE:
                $sparqlQuery = $this->getSparqlQueryForDescribe();
                break;
            case self::INSERT:
                $sparqlQuery = $this->getSparqlQueryForDeleteInsert();
                break;
            case self::DELETE:
                $sparqlQuery = $this->getSparqlQueryForDeleteInsert();
                break;
            case self::DELETE_INSERT:
                $sparqlQuery = $this->getSparqlQueryForDeleteInsert();
                break;
            default:
                $sparqlQuery = $this->getSparqlQueryForConstruct();
                break;
        }

        $this->state = self::STATE_CLEAN;
        $this->sparqlQuery = $sparqlQuery;

        return $sparqlQuery;
    }

    /**
     * Gets a string representation of this QueryBuilder which corresponds to
     * the final sparql query being constructed.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getSparqlQuery();
    }

    /**
     * Reset query parts.
     *
     * @return $this
     */
    public function reset()
    {
        $this->offset = -1;
        $this->maxResults = 0;
        $this->type = self::CONSTRUCT;

        foreach ($this->sparqlParts as $key => $part) {
            $this->sparqlParts[$key] = is_array($this->sparqlParts[$key]) ? array() : null;
            $this->state = self::STATE_DIRTY;
        }

        return $this;
    }

    /**
     * @param $construct
     * @param bool $append
     *
     * @return $this|QueryBuilder
     */
    protected function addConstructToQuery($construct, $append = false)
    {
        $this->type = self::CONSTRUCT;

        if (empty($construct)) {
            return $this;
        }

        $construct = is_array($construct) ? $construct : [$construct];
        $construct = new QueryBuilder\Expr\Construct($construct);

        return $this->add('construct', $construct, $append);
    }

    /**
     * @param $select
     * @param bool $append
     *
     * @return $this|QueryBuilder
     */
    protected function addSelectToQuery($select, $append = false)
    {
        $this->type = self::SELECT;

        if (empty($select)) {
            throw new InvalidArgumentException('You must specify what you want to select');
        }

        $select = is_array($select) ? $select : [$select];
        $select = new QueryBuilder\Expr\Select($select);

        return $this->add('select', $select, $append);
    }

    /**
     * @param $describe
     * @param bool $append
     *
     * @return $this|QueryBuilder
     */
    protected function addDescribeToQuery($describe, $append = false)
    {
        $this->type = self::DESCRIBE;

        if (empty($describe)) {
            throw new InvalidArgumentException('You must specify what you want to describe');
        }

        $describe = is_array($describe) ? $describe : [$describe];
        $describe = new QueryBuilder\Expr\Describe($describe);

        return $this->add('describe', $describe, $append);
    }

    protected function addAskToQuery($ask, $append = false)
    {
        $this->type = self::ASK;

        if (empty($ask)) {
            return $this;
        }

        $ask = is_array($ask) ? $ask : [$ask];
        $ask = new QueryBuilder\Expr\Ask($ask);

        return $this->add('ask', $ask, $append);
    }

    /**
     * @param $select
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addInsertToQuery($select, $append)
    {
        if (empty($select)) {
            throw new InvalidArgumentException('You must specify what you want to select');
        }

        if ($this->type == self::DELETE) {
            $this->type = self::DELETE_INSERT;
        } else {
            $this->type = self::INSERT;
        }

        $select = is_array($select) ? $select : [$select];
        $select = new QueryBuilder\Expr\Insert($select);

        return $this->add('insert', $select, $append);
    }

    /**
     * @param $delete
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addDeleteToQuery($delete, $append)
    {
        if ($this->type == self::INSERT) {
            $this->type = self::DELETE_INSERT;
        } else {
            $this->type = self::DELETE;
        }

        if (empty($delete)) {
            return $this;
        }

        if (strstr($delete, '[') || strstr($delete, ']') || strstr($delete, '_:')) {
            throw new InvalidArgumentException('You can not use a blank node in deletion');
        }

        $delete = is_array($delete) ? $delete : [$delete];
        $delete = new QueryBuilder\Expr\Delete($delete);

        return $this->add('delete', $delete, $append);
    }

    /**
     * @param $where
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addWhereToQuery($where, $append)
    {
        if (empty($where)) {
            throw new InvalidArgumentException('You must specify with which property you want to filter');
        }

        $where = is_array($where) ? $where : [$where];
        $where = new QueryBuilder\Expr\Where($where);

        return $this->add('where', $where, $append);
    }

    /**
     * @param $arrayPredicates
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addUnionToQuery($arrayPredicates, $append)
    {
        if (!is_array($arrayPredicates)) {
            throw new UnexpectedTypeException('', 'array');
        }

        if (count($arrayPredicates) < 2) {
            throw new InvalidArgumentException('The union must have at least two parts');
        }

        return $this->add('where', new QueryBuilder\Expr\Union($arrayPredicates), $append);
    }

    protected function addValuesToQuery($key, $value, $append)
    {
        if (is_array($value) && is_array($key)) {
            foreach ($value as $valKey => $val) {
                return $this->addValueToQuery($key[$valKey], $val, $append);
            }
        } elseif (is_string($value) && is_string($key)) {
            return $this->addValueToQuery($key, $value, $append);
        }
    }

    protected function addValueToQuery($key, $value, $append)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('You must specify a correct value');
        }
        if (empty($key)) {
            throw new InvalidArgumentException('You must specify correct type');
        }

        return $this->add('value', new QueryBuilder\Expr\Value($key, $value), $append);
    }

    /**
     * @param $value
     * @param $key
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addBindToQuery($value, $key, $append)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('You must specify what you want to bind');
        }

        if (is_string($value)) {
            return $this->add('bind', new QueryBuilder\Expr\Bind('('.$value.')'.' AS '.$key), $append);
        } else {
            return $this->add('bind', new QueryBuilder\Expr\Bind(is_array($value) ? $value : func_get_args()), $append);
        }
    }

    /**
     * @param $optional
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addOptionalToQuery($optional, $append)
    {
        if (empty($optional)) {
            throw new InvalidArgumentException('You must specify what you want to render optional');
        }
        $optional = is_array($optional) ? $optional : [$optional];
        $optional = new QueryBuilder\Expr\Optional($optional);

        return $this->add('optional', $optional, $append);
    }

    /**
     * @param $filter
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addFilterToQuery($filter, $append)
    {
        if (empty($filter)) {
            throw new InvalidArgumentException('You must specify what you want to filter');
        }

        $filter = is_array($filter) ? $filter : [$filter];
        $filter = new QueryBuilder\Expr\Filter($filter);

        return $this->add('filter', $filter, $append);
    }

    /**
     * @param $sort
     * @param $order
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addOrderByToQuery($sort, $order, $append)
    {
        if (empty($sort)) {
            throw new InvalidArgumentException('You must specify which property you want to sort with');
        }

        if ($this->offset == -1) {
            $this->offset = 0;
        }

        $orderBy = ($sort instanceof QueryBuilder\Expr\OrderBy) ? $sort : new QueryBuilder\Expr\OrderBy($sort, $order);

        return $this->add('orderBy', $orderBy, $append);
    }

    /**
     * @param $groupBy
     * @param $append
     *
     * @return QueryBuilder
     */
    protected function addGroupByToQuery($groupBy, $append)
    {
        if (empty($groupBy)) {
            throw new InvalidArgumentException('You must specify which property you want to group by');
        }

        $groupBy = new GroupBy([$groupBy]);

        return $this->add('groupBy', $groupBy, $append);
    }

    /**
     * Either appends to or replaces a single, generic query part.
     *
     * @param $sparqlPartName
     * @param $sparqlPart
     * @param bool $append
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    protected function add($sparqlPartName, $sparqlPart, $append = false)
    {
        $isMultiple = is_array($this->sparqlParts[$sparqlPartName]);

        if ($append && $isMultiple) {
            if (is_array($sparqlPart)) {
                $key = key($sparqlPart);

                $this->sparqlParts[$sparqlPartName][$key][] = $sparqlPart[$key];
            } else {
                $this->sparqlParts[$sparqlPartName][] = $sparqlPart;
            }
        } else {
            $this->sparqlParts[$sparqlPartName] = ($isMultiple) ? array($sparqlPart) : $sparqlPart;
        }

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Gets the complete sparql string formed by the current specifications of this QueryBuilder.
     *
     * @return string The sparql query string.
     */
    protected function getSparqlQueryForConstruct()
    {
        $sparqlQuery = 'CONSTRUCT '
            .($this->sparqlParts['distinct'] === true ? ' DISTINCT' : '')
            .$this->getReducedSparqlQueryPart('construct', array('pre' => '{ ', 'separator' => ' . ', 'post' => ' } '));

        $sparqlQuery .= $this->getWhereSparqlQueryPart();
        $sparqlQuery .= $this->getEndSparqlQueryPart();

        return $sparqlQuery;
    }

    protected function getSparqlQueryForDescribe()
    {
        $sparqlQuery = 'DESCRIBE'
            .($this->sparqlParts['distinct'] === true ? ' DISTINCT' : '')
            .$this->getReducedSparqlQueryPart('describe', array('pre' => ' ', 'separator' => ' ', 'post' => ' '));

        $sparqlQuery .= $this->getWhereSparqlQueryPart();
        $sparqlQuery .= $this->getEndSparqlQueryPart();

        return $sparqlQuery;
    }

    /**
     * Gets the complete sparql string formed by the current specifications of this QueryBuilder.
     *
     * @return string The sparql query string.
     */
    protected function getSparqlQueryForSelect()
    {
        $sparqlQuery = 'SELECT'
            .($this->sparqlParts['distinct'] === true ? ' DISTINCT' : '')
            .$this->getReducedSparqlQueryPart('select', array('pre' => ' ', 'separator' => ' ', 'post' => ' '));

        $sparqlQuery .= $this->getWhereSparqlQueryPart();
        $sparqlQuery .= $this->getEndSparqlQueryPart();

        return $sparqlQuery;
    }

    /**
     * Gets the complete sparql string formed by the current specifications of this QueryBuilder.
     *
     * @return string The sparql query string.
     */
    protected function getSparqlQueryForAsk()
    {
        $sparqlQuery = 'ASK ';
        $sparqlQuery .= $this->getReducedSparqlQueryPart('ask', array('pre' => '{ ', 'separator' => ' . ', 'post' => ' } '));
        $sparqlQuery .= $this->getWhereSparqlQueryPart();

        return $sparqlQuery;
    }

    /**
     * Gets the complete sparql string formed by the current specifications of this QueryBuilder.
     *
     * @return string
     */
    protected function getSparqlQueryForDeleteInsert()
    {
        $sparqlQuery = '';

        if (($this->type == self::DELETE || $this->type == self::DELETE_INSERT) && count($this->getSparqlPart('delete')) == 0) {
            $sparqlQuery .= 'DELETE ';
        } else {
            $sparqlQuery = $this->getReducedSparqlQueryPart('delete', array('pre' => 'DELETE { ', 'separator' => ' . ', 'post' => ' } '));
        }

        if ($this->type == self::INSERT || $this->type == self::DELETE_INSERT) {
            $sparqlQuery .= $this->getReducedSparqlQueryPart('insert', array('pre' => 'INSERT { ', 'separator' => ' . ', 'post' => ' } '));
        }

        $where = $this->getWhereSparqlQueryPart();

        return $sparqlQuery.(!empty($where) ? $where : ' WHERE {} ');
    }

    /**
     * Gets the where sparql query part.
     *
     * @return string
     */
    protected function getWhereSparqlQueryPart()
    {
        $array = array();
        $array['optional'] = $this->getReducedSparqlQueryPart('optional', array('pre' => '', 'separator' => '. ', 'post' => ' '));
        $array['filter'] = $this->getReducedSparqlQueryPart('filter', array('pre' => '', 'separator' => '. ', 'post' => ' '));
        $array['bind'] = $this->getReducedSparqlQueryPart('bind', array('pre' => '', 'separator' => '. ', 'post' => ' '));
        $array['value'] = $this->getReducedSparqlQueryPart('value', array('pre' => 'VALUES ', 'separator' => '. ', 'post' => ' '));

        $concat = false;
        $added = '';
        foreach ($array as $string) {
            if (!empty($string)) {
                if ($concat) {
                    $added .= '. '.$string;
                } else {
                    $added = $string;
                }
                $concat = true;
            }
        }

        if (!empty($added)) {
            $added = ' . '.$added;
        }

        if (count($this->getSparqlPart('where')) == 0 && !empty($added)) {
            return 'WHERE { '.$added.' }';
        } else {
            return $this->getReducedSparqlQueryPart('where', array('pre' => 'WHERE { ', 'separator' => ' . ', 'post' => $added.' } '));
        }
    }

    /**
     * Gets the complete post where sparql query string.
     *
     * @return string
     */
    protected function getEndSparqlQueryPart()
    {
        $sparqlQuery = '';
        $sparqlQuery .= $this->getReducedSparqlQueryPart('groupBy', array('pre' => 'GROUP BY ', 'separator' => ' . ', 'post' => ' '));

        return $sparqlQuery;
    }

    /**
     * @param $queryPartName
     *
     * @return mixed
     */
    protected function getSparqlPart($queryPartName)
    {
        return $this->sparqlParts[$queryPartName];
    }

    /**
     * @param $queryPartName
     * @param array $options
     *
     * @return string
     */
    protected function getReducedSparqlQueryPart($queryPartName, $options = array())
    {
        $queryPart = $this->getSparqlPart($queryPartName);

        if (empty($queryPart)) {
            return (isset($options['empty']) ? $options['empty'] : '');
        }

        return (isset($options['pre']) ? $options['pre'] : '')
            .(is_array($queryPart) ? implode($options['separator'], $queryPart) : $queryPart)
            .(isset($options['post']) ? $options['post'] : '');
    }
}
