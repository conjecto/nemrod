<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
