<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 13/01/2015
 * Time: 11:40
 */

namespace Devyn\QueryBuilder\Expr;


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
        'Devyn\\QueryBuilder\\Expr\\GroupExpr',
        'Devyn\\QueryBuilder\\Expr\\Filter',
        'Devyn\\QueryBuilder\\Expr\\Optional',
        'Devyn\\QueryBuilder\\Expr\\Bind',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}