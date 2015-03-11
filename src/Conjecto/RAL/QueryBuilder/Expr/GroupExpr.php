<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 12/01/2015
 * Time: 09:37
 */

namespace Conjecto\RAL\QueryBuilder\Expr;


use Doctrine\ORM\Query\Expr\Base;

class GroupExpr extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = '';

    /**
     * @var string
     */
    protected $separator = ' . ';

    /**
     * @var string
     */
    protected $postSeparator = '';

    /**
     * @var array
     */
    protected $allowedClasses = array(
        'Conjecto\\RAL\\QueryBuilder\\Expr\\Filter',
        'Conjecto\\RAL\\QueryBuilder\\Expr\\Optional',
        'Conjecto\\RAL\\QueryBuilder\\Expr\\Bind',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
} 