<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 12/01/2015
 * Time: 15:17
 */

namespace Devyn\QueryBuilder\Expr;


use Doctrine\ORM\Query\Expr\Base;

class OrderBy extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'ORDER BY ';

    /**
     * @var string
     */
    protected $separator = ' ';

    /**
     * @var string
     */
    protected $postSeparator = ' ';

    /**
     * @param string|null $sort
     * @param string|null $order
     */
    public function __construct($sort = null, $order = null)
    {
        if ($sort) {
            $this->add($sort, $order);
        }
    }

    /**
     * @param string      $sort
     * @param string|null $order
     *
     * @return void
     */
    public function add($sort, $order = null)
    {
        if ($order == 'ASC') {
            $this->parts[] = $sort;
        }
        else {
            $this->parts[] = 'DESC(' . $sort . ')';
        }
    }

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}