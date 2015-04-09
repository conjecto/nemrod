<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Event;

use Conjecto\Nemrod\Manager;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreFlushEvent.
 */
class PreFlushEvent extends Event
{
    protected $changes;

    /**
     * @var Manager $rm
     */
    protected $rm;

    /**
     *
     */
    public function __construct($changes, $rm)
    {
        $this->changes = $changes;
        $this->rm = $rm;
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

    /**
     * @return mixed
     */
    public function getRm()
    {
        return $this->rm;
    }

    /**
     * @param mixed $rm
     */
    public function setRm($rm)
    {
        $this->rm = $rm;
    }
}
