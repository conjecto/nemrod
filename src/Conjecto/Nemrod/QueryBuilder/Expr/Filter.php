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

use Conjecto\Nemrod\QueryBuilder\Expr\Base;

class Filter extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'FILTER (';

    /**
     * @var string
     */
    protected $separator = '';

    /**
     * @var string
     */
    protected $postSeparator = ')';

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @var array
     */
    protected $allowedClasses = array(
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\GroupExpr',
    );

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->count() == 1) {
            return $this->preSeparator.$this->parts[0].$this->postSeparator;
        }

        return $this->preSeparator.implode($this->separator, $this->parts).$this->postSeparator;
    }
}
