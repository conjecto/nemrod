<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 11/02/2015
 * Time: 10:20.
 */

namespace Conjecto\Nemrod\ResourceManager\Event;

use Conjecto\Nemrod\Manager;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ClearEvent.
 */
class ClearEvent extends Event
{
    /** @var  Manager */
    private $rm;

    /**
     * @param $rm
     */
    public function __construct($rm)
    {
        $this->rm = $rm;
    }

    /**
     * @return Manager
     */
    public function getManager()
    {
        return $this->rm;
    }

    /**
     * @param Manager $rm
     */
    public function setManager($rm)
    {
        $this->rm = $rm;
    }
}
