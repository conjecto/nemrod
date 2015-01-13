<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 06/01/2015
 * Time: 15:49
 */

namespace Devyn\Component\QueryBuilder\Expr;

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
    protected $separator = ' } UNION { ';

    /**
     * @var string
     */
    protected $postSeparator = ' } ';

    public function __construct($arrayPredicates)
    {
        return $this->addMultiple($arrayPredicates);
    }

    protected $allowedClasses = array(
        'Devyn\\Component\\QueryBuilder\\Expr\\GroupExpr'
    );

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
} 