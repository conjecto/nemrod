<?php
/**
 * Auteur: Blaise de Carné - blaise@concretis.com
 */

namespace Devyn\Bundle\RdfFrameworkBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class MimeTypeRequestListener
 * @package Devyn\Bundle\RdfFrameworkBundle\EventListener
 */
class MimeTypeRequestListener
{
    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $event->getRequest()->setFormat('jsonld', 'application/ld+json');
    }
}
