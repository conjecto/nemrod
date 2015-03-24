<?php
/**
 * Auteur: Blaise de Carné - blaise@concretis.com.
 */

namespace Conjecto\Nemrod\Bundle\NemrodBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class MimeTypeRequestListener.
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
