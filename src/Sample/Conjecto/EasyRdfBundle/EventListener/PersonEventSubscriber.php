<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/02/2015
 * Time: 12:07
 */

namespace Conjecto\EasyRdfBundle\EventListener;


use Devyn\Component\RAL\Manager\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PersonEventSubscriber implements EventSubscriberInterface{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PreFlush => 'onPreFlush',
            Events::PostFlush => 'onPostFlush'
        );
    }

    /**
     *
     */
    public function onPreFlush($event)
    {
        //foreach ($event->getResources() as $re)
        //{
            //$re->set('foaf:name',"Michel");
        //}
    }

    /**
     * @param $event
     */
    public function onPostFlush($event)
    {
        var_dump($event->getUris());
    }

} 