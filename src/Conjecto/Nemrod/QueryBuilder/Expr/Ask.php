<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 13/01/2015
 * Time: 11:40.
 */

namespace Conjecto\Nemrod\QueryBuilder\Expr;

use Doctrine\ORM\Query\Expr\Base;

class Ask extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = '{ ';

    /**
     * @var string
     */
    protected $separator = ' . ';

    /**
     * @var string
     */
    protected $postSeparator = ' } ';

    /**
     * @var array
     */
    protected $allowedClasses = array(
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\GroupExpr',
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\Filter',
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\Optional',
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\Bind',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
