<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 13/01/2015
 * Time: 11:40
 */

namespace Devyn\Component\QueryBuilder\Expr;


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
        'Devyn\\Component\\QueryBuilder\\Expr\\GroupExpr',
        'Devyn\\Component\\QueryBuilder\\Expr\\Filter',
        'Devyn\\Component\\QueryBuilder\\Expr\\Optional',
        'Devyn\\Component\\QueryBuilder\\Expr\\Bind',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}