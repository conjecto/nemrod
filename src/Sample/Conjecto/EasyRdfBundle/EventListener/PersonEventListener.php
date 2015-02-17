<?php

namespace Conjecto\EasyRdfBundle\EventListener;

use Devyn\Component\RAL\Manager\Event\PreFlushEvent;
use Devyn\Component\RAL\Manager\Event\ResourceLifeCycleEvent;
use Symfony\Component\EventDispatcher\Event;

class PersonEventListener
{

    public function onPreFlush(PreFlushEvent $evt)
    {
        echo '<pre>';
        print_r($evt->getChanges());
        echo '</pre>';
    }
} 