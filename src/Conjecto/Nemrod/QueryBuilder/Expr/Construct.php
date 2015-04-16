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


class Construct extends Base
{
    /**
     * @var string
     */
    protected $preSeparator = 'CONSTRUCT { ';

    /**
     * @var string
     */
    protected $separator = ' . ';

    /**
     * @var string
     */
    protected $postSeparator = ' } ';

    /**
     * @var array
     */
    protected $allowedClasses = array(
        'Conjecto\\Nemrod\\QueryBuilder\\Expr\\GroupExpr',
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
