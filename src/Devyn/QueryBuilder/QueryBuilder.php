<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 06/01/2015
 * Time: 09:49
 */

namespace Devyn\QueryBuilder;


use Devyn\Component\RAL\Registry\RdfNamespaceRegistry;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Expr\GroupBy;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Andx;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Class QueryBuilder
 * @package Test\FormBundle\QueryBuilder
 */
class QueryBuilder
{
    /* The query types. */
    const CONSTRUCT = 0;
    const DESCRIBE  = 1;
    const SELECT    = 2;
    const ASK       = 3;

    /* The builder states. */
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /**
     * query type
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
     * array if multiple, null if only one with multiples parts
     * @var array
     */
    protected $sparqlParts = array(
        'construct'  => array(),
        'where'   => array(),
        'optional' => array(),
        'filter' => array(),
        'bind' => array(),
        'orderBy' => array(),
        'groupBy' => array(),
        'distinct' => false
    );

    /**
     * result size limit
     * @var int
     */
    protected $maxResults;

    /**
     * query offset
     * @var int
     */
    protected $offset;

    /**
     * sparql query as string
     * @var string
     */
    protected $sparqlQuery;

    /**
     * namespace registry to add prefix to the query
     * @var RdfNamespaceRegistry
     */
    protected $nsRegistry;

    /**
     * @param RdfNamespaceRegistry $nsRegistry
     */
    function __construct(RdfNamespaceRegistry $nsRegistry)
    {
        $this->nsRegistry = $nsRegistry;
        $this->limit = 0;
        $this->offset = 0;
    }

    /**
     * declare a new construct query
     * @param null $construct
     * @return $this|QueryBuilder
     */
    public function construct($construct = null)
    {
        return $this->addConstructToQuery($construct, false);
    }

    /**
     * @param null $construct
     * @return $this|QueryBuilder
     */
    public function addConstruct($construct = null)
    {
        return $this->addConstructToQuery($construct, true);
    }

    /**
     * @param $where
     * @return QueryBuilder
     */
    public function where($where)
    {
        return $this->addWhereToQuery($where, false);
    }

    /**
     * @param $where
     * @return QueryBuilder
     */
    public function andWhere($where)
    {
        return $this->addWhereToQuery($where, true);
    }

    /**
     * @param $optional
     * @return QueryBuilder
     */
    public function optional($optional)
    {
        return $this->addOptionalToQuery($optional, false);
    }

    /**
     * @param $optional
     * @return QueryBuilder
     */
    public function addOptional($optional)
    {
        return $this->addOptionalToQuery($optional, true);
    }

    /**
     * @param $filter
     * @return QueryBuilder
     */
    public function filter($filter)
    {
        return $this->addFilterToQuery($filter, false);
    }

    /**
     * @param $filter
     * @return QueryBuilder
     */
    public function addFilter($filter)
    {
        return $this->addFilterToQuery($filter, true);
    }

    /**
     * @param $sort
     * @param null $order
     * @return QueryBuilder
     */
    public function orderBy($sort, $order = null)
    {
        return $this->addOrderByToQuery($sort, $order, false);
    }

    /**
     * @param $sort
     * @param null $order
     * @return QueryBuilder
     */
    public function addOrderBy($sort, $order = null)
    {
        return $this->addOrderByToQuery($sort, $order, true);
    }

    /**
     * @param $groupBy
     * @return QueryBuilder
     */
    public function groupBy($groupBy)
    {
        return $this->addGroupByToQuery($groupBy, false);
    }

    /**
     * @param $groupBy
     * @return QueryBuilder
     */
    public function addGroupBy($groupBy)
    {
        return $this->addGroupByToQuery($groupBy, true);
    }

    /**
     * @param $value
     * @param null $key
     * @return QueryBuilder
     */
    public function bind($value, $key = null)
    {
        return $this->addBindToQuery($value, $key, false);
    }

    /**
     * @param $value
     * @param null $key
     * @return QueryBuilder
     */
    public function addBind($value, $key = null)
    {
        return $this->addBindToQuery($value, $key, true);
    }

    /**
     * @param $arrayPredicates
     * @return QueryBuilder
     */
    public function union($arrayPredicates)
    {
        return $this->addUnionToQuery($arrayPredicates, false);
    }

    /**
     * @param $arrayPredicates
     * @return QueryBuilder
     */
    public function addUnion($arrayPredicates)
    {
        return $this->addUnionToQuery($arrayPredicates, true);
    }

    /**
     * @param $maxResults
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    /**
     * @param $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     *
     */
    public function getQuery()
    {
//        $parameters = clone $this->parameters;
//
//        return $this->_em->createQuery($this->getDQL())
//            ->setParameters($parameters)
//            ->setFirstResult($this->_firstResult)
//            ->setMaxResults($this->_maxResults);
    }

    /**
     * Return the query as a string
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
            default:
                $sparqlQuery = $this->getSparqlQueryForConstruct();
                break;
        }

        $this->state = self::STATE_CLEAN;
        $this->sparqlQuery = $sparqlQuery;

        return $sparqlQuery;
    }

    /**
     * @param $queryPartName
     * @return mixed
     */
    public function getSparqlPart($queryPartName)
    {
        return $this->sparqlParts[$queryPartName];
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->getSparql();
    }

    /**
     * @param $construct
     * @param bool $append
     * @return $this|QueryBuilder
     */
    protected function addConstructToQuery($construct, $append = false)
    {
        $this->type = self::CONSTRUCT;

        if (empty($construct)) {
            return $this;
        }

        $construct = is_array($construct) ? $construct : [$construct];
        $construct = new Expr\Construct($construct);
        return $this->add('construct', $construct, $append);
    }

    /**
     * @param $where
     * @param $append
     * @return QueryBuilder
     */
    protected function addWhereToQuery($where, $append)
    {
        $where = is_array($where) ? $where : [$where];
        $where = new Expr\Where($where);
        return $this->add('where', $where, $append);
    }

    /**
     * @param $arrayPredicates
     * @param $append
     * @return QueryBuilder
     */
    protected function addUnionToQuery($arrayPredicates, $append)
    {
        if (!is_array($arrayPredicates)) {
            throw new \Symfony\Component\Validator\Exception\UnexpectedTypeException('', 'array');
        }

        if (count($arrayPredicates) < 2) {
            throw new InvalidArgumentException('The union must have at least two parts');
        }

        return $this->add('where', new Expr\Union($arrayPredicates), $append);
    }

    /**
     * @param $value
     * @param $key
     * @param $append
     * @return QueryBuilder
     */
    protected function addBindToQuery($value, $key, $append)
    {
        if (is_string($value)) {
            return $this->add('bind', new Expr\Bind('"' . $value . '"' . ' AS ' . $key), $append);
        }
        else {
            return $this->add('bind', new Expr\Bind(is_array($predicates) ? $predicates : func_get_args()), $append);
        }
    }

    /**
     * @param $optional
     * @param $append
     * @return QueryBuilder
     */
    protected function addOptionalToQuery($optional, $append)
    {
        $optional = is_array($optional) ? $optional : [$optional];
        $optional = new Expr\Optional($optional);
        return $this->add('optional', $optional, $append);
    }

    /**
     * @param $filter
     * @param $append
     * @return QueryBuilder
     */
    protected function addFilterToQuery($filter, $append)
    {
        $filter = is_array($filter) ? $filter : [$filter];
        $filter = new Expr\Filter($filter);
        return $this->add('filter', $filter, $append);
    }

    /**
     * @param $sort
     * @param $order
     * @param $append
     * @return QueryBuilder
     */
    protected function addOrderByToQuery($sort, $order, $append)
    {
        $orderBy = ($sort instanceof OrderBy) ? $sort : new OrderBy($sort, $order);
        return $this->add('orderBy', $orderBy, $append);
    }

    /**
     * @param $groupBy
     * @param $append
     * @return QueryBuilder
     */
    protected function addGroupByToQuery($groupBy, $append)
    {
        $groupBy = new GroupBy([$groupBy]);
        return $this->add('groupBy', $groupBy, $append);
    }

    /**
     * Add a new expression to the query
     * @param $sparqlPartName
     * @param $sparqlPart
     * @param bool $append
     * @return $this
     */
    protected function add($sparqlPartName, $sparqlPart, $append = false)
    {
//        if ($append && ($sparqlPartName === "where" || $sparqlPartName === "having")) {
//            throw new \InvalidArgumentException(
//                "Using \$append = true does not have an effect with 'where' or 'having' ".
//                "parts. See QueryBuilder#andWhere() for an example for correct usage."
//            );
//        }

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

        $this->_state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Return the query as a string for construct query
     * @return string
     */
    protected function getSparqlQueryForConstruct()
    {
        $sparqlQuery = 'CONSTRUCT'
            . ($this->sparqlParts['distinct']===true ? ' DISTINCT' : '')
            . $this->getReducedSparqlQueryPart('construct', array('pre' => ' { ', 'separator' => ' . ', 'post' => ' } '));

        $sparqlQuery .= $this->getReducedSparqlQueryPart('where', array('pre' => 'WHERE { ', 'separator' => ' . ', 'post' =>
             $this->getReducedSparqlQueryPart('optional', array('pre' => ' . ', 'separator' => '. ', 'post' => ''))
            . $this->getReducedSparqlQueryPart('filter', array('pre' => ' . ', 'separator' => '. ', 'post' => ''))
            . $this->getReducedSparqlQueryPart('bind', array('pre' => ' . ', 'separator' => '. ', 'post' => ''))
            . ' } '
        ));

        $sparqlQuery .= $this->getReducedSparqlQueryPart('orderBy', array('pre' => 'ORDER BY ', 'separator' => ' . ', 'post' => ' '));
        $sparqlQuery .= $this->getReducedSparqlQueryPart('groupBy', array('pre' => 'GROUP BY ', 'separator' => ' . ', 'post' => ' '));
        if ($this->offset > 0)
            $sparqlQuery .= 'OFFSET ' . strval($this->offset) . ' ';
        if ($this->maxResults > 0)
            $sparqlQuery .= 'LIMIT ' . strval($this->maxResults) . ' ';

        return $sparqlQuery;
    }

    /**
     * @param $sparqlQuery
     * @return string
     */
    protected function getPrefixesFromQuery($sparqlQuery)
    {
        $prefixes = '';
        foreach ($this->nsRegistry->namespaces() as $key=>$namespace) {
            if (strstr($sparqlQuery, $key . ':')) {
                $prefixes .= 'PREFIX ' . $key . ': ' . $namespace . ' ';
            }
        }

        return $prefixes;
    }

    /**
     * @param $queryPartName
     * @param array $options
     * @return string
     */
    protected function getReducedSparqlQueryPart($queryPartName, $options = array())
    {
        $queryPart = $this->getSparqlPart($queryPartName);

        if (empty($queryPart)) {
            return (isset($options['empty']) ? $options['empty'] : '');
        }

        return (isset($options['pre']) ? $options['pre'] : '')
            . (is_array($queryPart) ? implode($options['separator'], $queryPart) : $queryPart)
            . (isset($options['post']) ? $options['post'] : '');
    }
}