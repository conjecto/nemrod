<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\QueryBuilder\Expr;

class OrderBy extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'ORDER BY ';

    /**
     * @var string
     */
    protected $separator = ' ';

    /**
     * @var string
     */
    protected $postSeparator = ' ';

    /**
     * @param string|null $sort
     * @param string|null $order
     */
    public function __construct($sort = null, $order = null)
    {
        if ($sort) {
            $this->add($sort, $order);
        }
    }

    /**
     * @param string      $sort
     * @param string|null $order
     */
    public function add($sort, $order = null)
    {
        if (strtoupper($order) !== 'DESC') {
            $this->parts[] = $sort;
        } else {
            $this->parts[] = 'DESC('.$sort.')';
        }
    }

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
