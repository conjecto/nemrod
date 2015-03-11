<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 12/01/2015
 * Time: 15:01
 */

namespace Conjecto\RAL\QueryBuilder\Expr;


use Doctrine\ORM\Query\Expr\Base;

class Select extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'SELECT ';

    /**
     * @var string
     */
    protected $separator = ' ';

    /**
     * @var string
     */
    protected $postSeparator = ' ';

    /**
     * @var array
     */
    protected $allowedClasses = array(
        'Conjecto\\RAL\\QueryBuilder\\Expr\\GroupExpr',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}