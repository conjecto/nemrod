<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 19/01/2015
 * Time: 14:04
 */

namespace Devyn\Component\QueryBuilder;


use Devyn\Component\QueryBuilder\Query;

class Parser
{
    /**
     * @var Query
     */
    protected $query;

    function __construct($query)
    {
        $this->query = $query;
    }
} 