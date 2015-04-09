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
        parent::__construct($key.' { '.$value.'}');
    }

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
