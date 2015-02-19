<?php

namespace Devyn\Component\ESIndexing;

use Devyn\Component\RAL\Manager\Event\PreFlushEvent;
use Devyn\Component\RAL\Manager\Event\ResourceLifeCycleEvent;
use Symfony\Component\EventDispatcher\Event;

class ManagerEventListener
{
    public function onPreFlush(PreFlushEvent $evt)
    {
//        echo 'listener <pre>';
//        print_r($evt->getChanges());
//        echo '</pre>';
    }

    public function onPostFlush(PostFlushEvent $evt)
    {
//        echo 'listener <pre>';
//        print_r($evt->getChanges());
//        echo '</pre>';
    }
} 