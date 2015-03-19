<?php

namespace Conjecto\EasyRdfBundle\EventListener;

use Conjecto\RAL\ResourceManager\Manager\Event\PreFlushEvent;

class PersonEventListener
{
    public function onPreFlush(PreFlushEvent $evt)
    {
        echo '<pre>';
        print_r($evt->getChanges());
        echo '</pre>';
    }
}
