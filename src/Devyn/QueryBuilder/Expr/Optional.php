<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 06/01/2015
 * Time: 12:19
 */

namespace Devyn\QueryBuilder\Expr;

use Doctrine\ORM\Query\Expr\Base;

class Optional extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'OPTIONAL {';

    /**
     * @var string
     */
    protected $separator = ' . ';

    /**
     * @var string
     */
    protected $postSeparator = ' . } ';

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
} 