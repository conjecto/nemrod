<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 12/01/2015
 * Time: 17:32
 */

namespace Devyn\Component\QueryBuilder\Expr;


use Doctrine\ORM\Query\Expr\Base;

class Insert extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'INSERT { ';

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
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}