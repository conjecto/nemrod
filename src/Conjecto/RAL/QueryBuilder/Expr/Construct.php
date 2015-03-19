<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 06/01/2015
 * Time: 11:24.
 */

namespace Conjecto\RAL\QueryBuilder\Expr;

use Doctrine\ORM\Query\Expr\Base;

class Construct extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'CONSTRUCT { ';

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
