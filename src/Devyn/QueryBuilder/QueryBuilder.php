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
    const UPDATE    = 3;
    const ASK       = 4;

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
        'where'   => null,
        'orderBy' => array(),
        'groupBy' => array(),
        'optional' => array(),
        'filter' => array(),
        'union' => array(),
        'bind' => array(),
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
    }

    /**
     * declare a new construct request
     * @param null $construct
     * @return $this|QueryBuilder
     */
    public function construct($construct = null)
    {
        $this->type = self::CONSTRUCT;

        if (empty($construct)) {
            return $this;
        }

        $constructs = is_array($construct) ? $construct : func_get_args();

        return $this->add('construct', new Expr\Construct($constructs), false);
    }

    /**
     * @param null $construct
     * @return $this|QueryBuilder
     */
    public function addConstruct($construct = null)
    {
        $this->type = self::CONSTRUCT;

        if (empty($construct)) {
            return $this;
        }

        $constructs = is_array($construct) ? $construct : func_get_args();

        return $this->add('construct', new Expr\Construct($constructs), true);
    }

//    public function select()
//    {
//        $this->type = self::SELECT;
//    }
//
//    public function describe()
//    {
//        $this->type = self::DESCRIBE;
//    }
//
//    public function update()
//    {
//        $this->type = self::UPDATE;
//    }
//
//    public function ask()
//    {
//        $this->type = self::ASK;
//    }

    /**
     * Add where clause
     * One where only
     * @param $predicates
     * @return QueryBuilder
     */
    public function where($predicates)
    {
        if ( ! (func_num_args() == 1 && $predicates instanceof Composite)) {
            $predicates = new Andx(func_get_args());
        }

        return $this->add('where', $predicates);
    }

    /**
     * Add parts to the where
     * @param $where
     * @return QueryBuilder
     */
    public function andWhere($where)
    {
        $args  = func_get_args();
        $where = $this->getDQLPart('where');

        if ($where instanceof Andx) {
            $where->addMultiple($args);
        } else {
            array_unshift($args, $where);
            $where = new Andx($args);
        }

        return $this->add('where', $where);
    }


//    public function where($predicates)
//    {
//        $predicates = is_array($predicates) ? $predicates : func_get_args();
//        return $this->add('where', new Expr\Where($predicates), false);
//    }
//
//    public function addWhere($predicates)
//    {
//        $predicates = is_array($predicates) ? $predicates : func_get_args();
//        return $this->add('where',  new Expr\Where($predicates), true);
//    }

    public function optional($predicates)
    {
        $predicates = is_array($predicates) ? $predicates : func_get_args();
        return $this->add('optional', new Expr\Optional($predicates), false);
    }

    public function addOptional($predicates)
    {
        $predicates = is_array($predicates) ? $predicates : func_get_args();
        return $this->add('optional', new Expr\Optional($predicates), true);
    }

    public function filter($predicates)
    {
        $predicates = is_array($predicates) ? $predicates : func_get_args();
        return $this->add('filter', new Expr\Filter($predicates), false);
    }

    public function addFilter($predicates)
    {
        $predicates = is_array($predicates) ? $predicates : func_get_args();
        return $this->add('filter', new Expr\Filter($predicates), true);
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
        return $this->add('groupBy', new GroupBy(func_get_args()));
    }

    public function addGroupBy($groupBy)
    {
        return $this->add('groupBy', new GroupBy(func_get_args()), true);
    }

    public function bind($value, $key)
    {
        if (is_string($value)) {
            return $this->add('bind', new Expr\Bind('"' . $value . '"' . ' AS ' . $key), false);
        }
        else {
            return $this->add('bind', new Expr\Bind((string)$value . ' AS ' . $key), false);
        }
    }

    public function addBind($value, $key)
    {
        if (is_string($value)) {
            return $this->add('bind', new Expr\Bind('"' . $value . '"' . ' AS ' . $key), true);
        }
        else {
            return $this->add('bind', new Expr\Bind((string)$value . ' AS ' . $key), true);
        }
    }

    /**
     * @TODO correct this function
     * @param $leftPredicates
     * @param $rightPredicates
     * @return QueryBuilder
     */
    public function addUnion($leftPredicates, $rightPredicates)
    {
        if (!is_string($leftPredicates)) {
            throw new UnexpectedTypeException($leftPredicates, 'string');
        }
        if (!is_string($rightPredicates)) {
            throw new UnexpectedTypeException($rightPredicates, 'string');
        }
        return $this->add('where',  new Expr\Where([new Expr\Union($leftPredicates . ' } UNION { ' . $rightPredicates)]), true);
    }

    /**
     * @param $maxResults
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * Add a new expression to the request
     * @param $sparqlPartName
     * @param $sparqlPart
     * @param bool $append
     * @return $this
     */
    public function add($sparqlPartName, $sparqlPart, $append = false)
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
     * Return the request as a string for construct request
     * @return string
     */
    protected function getDQLForConstruct()
    {
        $dql = '';
//
//        foreach ($this->nsRegistry->namespaces() as $key=>$namespace) {
//            $dql .= 'PREFIX ' . $key . ': ' . $namespace . '\n';
//        }

        $dql .= 'CONSTRUCT'
            . ($this->sparqlParts['distinct']===true ? ' DISTINCT' : '')
            . $this->getReducedDQLQueryPart('construct', array('pre' => ' { ', 'separator' => ' . ', 'post' => ' . } '));

        $dql .= $this->getReducedDQLQueryPart('where', array('pre' => 'WHERE { ', 'separator' => ' . ', 'post' =>
            $this->getReducedDQLQueryPart('optional', array('pre' => ' OPTIONAL { ', 'separator' => ' . ', 'post' => ' . }'))
            . $this->getReducedDQLQueryPart('filter', array('pre' => ' FILTER ( ', 'separator' => ' . ', 'post' => ')'))
            . $this->getReducedDQLQueryPart('bind', array('pre' => ' BIND ( ', 'separator' => ' . ', 'post' => ')'))
            . ' } '
        ));

        $dql .= $this->getReducedDQLQueryPart('orderBy', array('pre' => 'ORDER BY ', 'separator' => ' . ', 'post' => ' '));
        $dql .= $this->getReducedDQLQueryPart('groupBy', array('pre' => 'GROUP BY ', 'separator' => ' . ', 'post' => ' '));
        if ($this->offset > 0)
            $dql .= 'OFFSET ' . strval($this->offset) . ' ';
        if ($this->maxResults > 0)
            $dql .= 'LIMIT ' . strval($this->maxResults) . ' ';

        return $dql;
    }

    /**
     * @param $queryPartName
     * @param array $options
     * @return string
     */
    private function getReducedDQLQueryPart($queryPartName, $options = array())
    {
        $queryPart = $this->getDQLPart($queryPartName);

        if (empty($queryPart)) {
            return (isset($options['empty']) ? $options['empty'] : '');
        }

        return (isset($options['pre']) ? $options['pre'] : '')
        . (is_array($queryPart) ? implode($options['separator'], $queryPart) : $queryPart)
        . (isset($options['post']) ? $options['post'] : '');
    }

    /**
     * @param $queryPartName
     * @return mixed
     */
    public function getDQLPart($queryPartName)
    {
        return $this->sparqlParts[$queryPartName];
    }

    public function execute()
    {

    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->getSparql();
    }
}