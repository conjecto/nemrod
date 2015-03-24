<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 06/01/2015
 * Time: 11:45.
 */

namespace Conjecto\Nemrod\QueryBuilder\Expr;

use Doctrine\ORM\Query\Expr\Base;

class Where extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'WHERE { ';

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
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\Union',
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\Filter',
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\Optional',
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\GroupExpr',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
