<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 11/02/2015
 * Time: 11:46
 */

namespace Devyn\Component\RAL\Manager\Event;


use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreFlushEvent
 * @package Devyn\Component\RAL\Manager\Event
 */
class PreFlushEvent extends Event{

    protected $changes;

    /**
     *
     */
    public function __construct($changes)
    {
        $this->changes = $changes;
    }

    /**
     * @return mixed
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @param mixed $changes
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;
    }


} 