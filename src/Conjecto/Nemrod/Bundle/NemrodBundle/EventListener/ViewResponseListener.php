<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\NemrodBundle\EventListener;

use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Class ViewResponseListener.
 */
class ViewResponseListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Guesses the template name to render and its variables and adds them to
     * the request object.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        //        $request = $event->getRequest();
//        $configuration = $request->attributes->get('_rdf_view');
        /*$request = $event->getRequest();

        if ($configuration = $request->attributes->get('_view')) {
            $request->attributes->set('_template', $configuration);
        }*/
    }

    /**
     * Renders the parameters and template and initializes a new response object with the
     * rendered content.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $configuration = $request->attributes->get('_rdf_view');
        $view = $event->getControllerResult();
        if ($configuration) {
            $context = new SerializationContext();

            // set format from _format
            $format = $request->attributes->get('_format');
            if (!$format) {
                $format = 'jsonld';
            }

            // set format from configuration
            if ($configuration->getFormat()) {
                $format = $configuration->getFormat();
            }

            // set frame (jsonld)
            if ($configuration->getFrame()) {
                $frame = $configuration->getFrame();
                $context->setAttribute('frame', $frame);
            }

            // set compact (jsonld)
            if ($configuration->isCompact()) {
                $compact = $configuration->isCompact();
                $context->setAttribute('compact', $compact);
            }

            // serialize
            $serializer = $this->container->get('jms_serializer');
            $response = new Response($serializer->serialize($view, $format, $context));

            // add mime type
            if (!$response->headers->has('Content-Type')) {
                $response->headers->set('Content-Type', $request->getMimeType($format));
            }

            // set the response
            $event->setResponse($response);

            return;
        }
    }
}
