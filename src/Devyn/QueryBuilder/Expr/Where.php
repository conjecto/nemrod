<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 06/01/2015
 * Time: 11:45
 */

namespace Devyn\QueryBuilder\Expr;

use Doctrine\ORM\Query\Expr\Base;

class Where extends Base
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
    protected $postSeparator = ' .';

    /**
     * @var array
     */
    protected $allowedClasses = array(
        'Test\\FormBundle\\QueryBuilder\\Expr\\Union',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
} 