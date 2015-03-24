<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 19/01/2015
 * Time: 14:04.
 */

namespace Conjecto\Nemrod\QueryBuilder;


class Parser
{
    /**
     * @var Query
     */
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }
}
