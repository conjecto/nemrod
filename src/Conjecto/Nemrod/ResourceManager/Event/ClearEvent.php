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
