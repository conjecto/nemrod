<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 06/01/2015
 * Time: 11:45
 */

namespace Devyn\Component\QueryBuilder\Expr;

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
        'Devyn\\Component\\QueryBuilder\\Expr\\Union',
        'Devyn\\Component\\QueryBuilder\\Expr\\Filter',
        'Devyn\\Component\\QueryBuilder\\Expr\\Optional',
        'Devyn\\Component\\QueryBuilder\\Expr\\GroupExpr',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}