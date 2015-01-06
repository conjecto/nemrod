<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 06/01/2015
 * Time: 15:49
 */

namespace Devyn\QueryBuilder\Expr;

use Doctrine\ORM\Query\Expr\Base;

class Union extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = ' { ';

    /**
     * @var string
     */
    protected $separator = '';

    /**
     * @var string
     */
    protected $postSeparator = ' } ';

    public function __construct($left, $right)
    {

    }

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }

    public function __toString()
    {
        return 'union';
    }
} 