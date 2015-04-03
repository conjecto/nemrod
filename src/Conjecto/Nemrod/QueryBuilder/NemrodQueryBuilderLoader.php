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
use Conjecto\Nemrod\Manager;
use EasyRdf\Graph;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NemrodQueryBuilderLoader
{
    /**
     * @var string
     */
    protected $class;

    /**
     * Construct an ORM Query Builder Loader.
     *
     * @param QueryBuilder|\Closure $queryBuilder
     * @param Manager               $manager
     * @param string                $class
     *
     * @throws UnexpectedTypeException
     */
    public function __construct($queryBuilder, $manager = null, $class = null)
    {
        // If a query builder was passed, it must be a closure or QueryBuilder
        // instance
        if (!($queryBuilder instanceof QueryBuilder || $queryBuilder instanceof \Closure)) {
            throw new UnexpectedTypeException($queryBuilder, 'Conjecto\Nemrod\QueryBuilder or \Closure');
        }

        if ($queryBuilder instanceof \Closure) {
            if (!$manager instanceof Manager) {
                throw new UnexpectedTypeException($manager, 'Conjecto\Nemrod\Manager');
            }

            $queryBuilder = $queryBuilder($manager->getRepository($class));

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new UnexpectedTypeException($queryBuilder, 'Conjecto\Nemrod\QueryBuilder');
            }
        }

        $this->queryBuilder = $queryBuilder;
        $this->class = $class;
    }

    /**
     * @param null  $hydratation
     * @param array $options
     *
     * @return Graph|\EasyRdf\Sparql\Result
     */
    public function getResources($hydratation = null, $options = array())
    {
        return $this->queryBuilder->getQuery()->execute($hydratation, $options);
    }
}
