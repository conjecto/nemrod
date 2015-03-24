<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 26/01/2015
 * Time: 14:17.
 */

namespace Conjecto\Nemrod\QueryBuilder\Internal\Hydratation;

use Conjecto\Nemrod\QueryBuilder\Query;
use Conjecto\Nemrod\ResourceManager\Manager\Manager;
use EasyRdf\Graph;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

abstract class AbstractHydrator
{
    /**
     * @var Manager
     */
    protected $rm;

    /**
     * @var Graph
     */
    protected $graph;

    /**
     * @param $rm
     * @param $graph
     */
    public function __construct(Query $query)
    {
        $this->rm = $query->getRm();
        $this->graph = $query->getResult();

        if (!$this->graph instanceof Graph) {
            throw new UnexpectedTypeException('Attempting a EasyRdf\Graph', $this->graph);
        }

        if (!$this->rm instanceof Manager) {
            throw new UnexpectedTypeException('Attempting a Conjecto\Nemrod\ResourceManager\Manager\Manager', $this->rm);
        }
    }

    abstract public function hydrateResources($options = array());
}
