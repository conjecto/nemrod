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
     * @var int
     */
    protected $offset;

    /**
     * sparql request as string
     * @var string
     */
    protected $sparqlRequest;

    /**
     * namespace registry to add prefix to the request
     * @var RdfNamespaceRegistry
     */
    protected $nsRegistry;

    function __construct(RdfNamespaceRegistry $nsRegistry)
    {
        $this->nsRegistry = $nsRegistry;
        $this->limit = 0;
        $this->offset = 0;
    }

    /**
     * declare a new construct request
     * @param null $construct
     * @return $this|QueryBuilder
     */
    public function construct($construct = null)
    {
        return $this->addConstructToQuery($construct, false);
    }

    public function addConstruct($construct = null)
    {
        return $this->addConstructToQuery($construct, true);
    }

    public function where($where)
    {
        $where = is_array($where) ? $where : [$where];
        return $this->add('where', new Expr\Where($where), false);
    }

    public function andWhere($where)
    {
        $where = is_array($where) ? $where : [$where];
        return $this->add('where', new Expr\Where($where), true);
    }

    public function optional($optional)
    {
        $optional = is_array($optional) ? $optional : [$optional];
        return $this->add('optional', new Expr\Optional($optional), false);
    }

    public function addOptional($optional)
    {
        $optional = is_array($optional) ? $optional : [$optional];
        return $this->add('optional', new Expr\Optional($optional), true);
    }

    public function filter($filter)
    {
        $filter = is_array($filter) ? $filter : [$filter];
        return $this->add('filter', new Expr\Filter($filter), false);
    }

    public function addFilter($filter)
    {
        $filter = is_array($filter) ? $filter : [$filter];
        return $this->add('filter', new Expr\Filter($filter), true);
    }

    public function orderBy($sort, $order = null)
    {
        $orderBy = ($sort instanceof OrderBy) ? $sort : new OrderBy($sort, $order);
        return $this->add('orderBy', $orderBy);
    }

    public function addOrderBy($sort, $order = null)
    {
        $orderBy = ($sort instanceof OrderBy) ? $sort : new OrderBy($sort, $order);
        return $this->add('orderBy', $orderBy, true);
    }

    public function groupBy($groupBy)
    {
        return $this->add('groupBy', new GroupBy([$groupBy]), false);
    }

    public function addGroupBy($groupBy)
    {
        return $this->add('groupBy', new GroupBy([$groupBy]), true);
    }

    public function bind($value, $key = null)
    {
        return $this->addBindToQuery($value, $key, false);
    }

    public function addBind($value, $key = null)
    {
        return $this->addBindToQuery($value, $key, true);
    }

    public function union($arrayPredicates)
    {
        return $this->addUnionToQuery($arrayPredicates, false);
    }

    public function addUnion($arrayPredicates)
    {
        return $this->addUnionToQuery($arrayPredicates, true);
    }

    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

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
     * Return the request as a string
     * @return string
     */
    public function getSparql()
    {
        if ($this->sparqlRequest !== null && $this->state === self::STATE_CLEAN) {
            return $this->sparqlRequest;
        }

        switch ($this->type) {
            case self::CONSTRUCT:
                $sparqlRequest = $this->getDQLForConstruct();
                break;
            default:
                $sparqlRequest = $this->getDQLForConstruct();
                break;
        }

        $this->state = self::STATE_CLEAN;
        $this->sparqlRequest = $sparqlRequest;

        return $sparqlRequest;
    }

    /**
     * @param $queryPartName
     * @return mixed
     */
    public function getDQLPart($queryPartName)
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

    protected function addConstructToQuery($construct, $append = false)
    {
        $this->type = self::CONSTRUCT;

        if (empty($construct)) {
            return $this;
        }

        if (!is_object($construct)) {
            $construct = is_array($construct) ? $construct : [$construct];
            $construct = new Expr\Construct($construct);
        }

        return $this->add('construct', $construct, $append);
    }

    protected function addUnionToQuery($arrayPredicates, $append)
    {
        if (!is_array($arrayPredicates)) {
            throw new \Symfony\Component\Validator\Exception\UnexpectedTypeException('', 'array');
        }

        if (count($arrayPredicates) < 2) {
            throw new InvalidArgumentException('The union has to have at least two parts');
        }

        return $this->add('where', new Expr\Union($arrayPredicates), $append);
    }

    protected function addBindToQuery($value, $key, $append)
    {
        if (is_string($value)) {
            return $this->add('bind', new Expr\Bind('"' . $value . '"' . ' AS ' . $key), $append);
        }
        else if ($value instanceof Expr\Bind) {
            return $this->add('bind', $value, $append);
        }
        else {
            return $this->add('bind', new Expr\Bind(is_array($predicates) ? $predicates : func_get_args()), $append);
        }
    }

    /**
     * Add a new expression to the request
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
     * Return the request as a string for construct request
     * @return string
     */
    protected function getDQLForConstruct()
    {
        $dql = 'CONSTRUCT'
            . ($this->sparqlParts['distinct']===true ? ' DISTINCT' : '')
            . $this->getReducedDQLQueryPart('construct', array('pre' => ' { ', 'separator' => ' . ', 'post' => ' } '));

        $dql .= $this->getReducedDQLQueryPart('where', array('pre' => 'WHERE { ', 'separator' => ' . ', 'post' =>
             $this->getReducedDQLQueryPart('optional', array('pre' => ' . ', 'separator' => '. ', 'post' => ''))
            . $this->getReducedDQLQueryPart('filter', array('pre' => ' . ', 'separator' => '. ', 'post' => ''))
            . $this->getReducedDQLQueryPart('bind', array('pre' => ' . ', 'separator' => '. ', 'post' => ''))
            . ' } '
        ));

        $dql .= $this->getReducedDQLQueryPart('orderBy', array('pre' => 'ORDER BY ', 'separator' => ' . ', 'post' => ' '));
        $dql .= $this->getReducedDQLQueryPart('groupBy', array('pre' => 'GROUP BY ', 'separator' => ' . ', 'post' => ' '));
        if ($this->offset > 0)
            $dql .= 'OFFSET ' . strval($this->offset) . ' ';
        if ($this->maxResults > 0)
            $dql .= 'LIMIT ' . strval($this->maxResults) . ' ';


        $prefixes = '';
        foreach ($this->nsRegistry->namespaces() as $key=>$namespace) {
            if (strstr($dql, $key . ':')) {
                $prefixes .= 'PREFIX ' . $key . ': ' . $namespace . ' ';
            }
        }

        return $dql;
    }

    /**
     * @param $queryPartName
     * @param array $options
     * @return string
     */
    protected function getReducedDQLQueryPart($queryPartName, $options = array())
    {
        $queryPart = $this->getDQLPart($queryPartName);

        if (empty($queryPart)) {
            return (isset($options['empty']) ? $options['empty'] : '');
        }

        return (isset($options['pre']) ? $options['pre'] : '')
        . (is_array($queryPart) ? implode($options['separator'], $queryPart) : $queryPart)
        . (isset($options['post']) ? $options['post'] : '');
    }
}