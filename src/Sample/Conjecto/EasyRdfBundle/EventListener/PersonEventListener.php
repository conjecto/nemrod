<?php

namespace Conjecto\EasyRdfBundle\EventListener;

use Conjecto\RAL\ResourceManager\Manager\Event\PreFlushEvent;
use Conjecto\RAL\ResourceManager\Manager\Event\ResourceLifeCycleEvent;
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