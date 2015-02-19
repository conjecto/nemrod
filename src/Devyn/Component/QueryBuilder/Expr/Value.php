<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 19/02/2015
 * Time: 10:40
 */

namespace Devyn\Component\QueryBuilder\Expr;

use Doctrine\ORM\Query\Expr\Base;

class Value extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'VALUE { ';

    /**
     * @var string
     */
    protected $separator = ' ';

    /**
     * @var string
     */
    protected $postSeparator = ' } ';

    public function __construct($key, $value)
    {
        parent::__construct($key  . ' { ' . $value . '}');
    }

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}