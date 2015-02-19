<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/02/2015
 * Time: 12:07
 */

namespace Devyn\Component\ESIndexing;


use Devyn\Component\RAL\Manager\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ManagerEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ESCache
     */
    protected $esCache;

    /**
     * @var array
     */
    protected $changesRequests;

    function __construct(ESCache $esCache)
    {
        $this->esCache = $esCache;
    }

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
        var_dump('onPreFlush');
//        var_dump($event);
        $this->changesRequests = [];
        foreach ($event->getChanges() as $key => $change) {
            $this->changesRequests[$key]['type'] = $change['type'];
            $this->changesRequests[$key]['qb'] = $this->esCache->getRequest('ogbd', $key, 'person');
            var_dump($this->esCache->getRequest('ogbd', $key, 'person')->getSparqlQuery());
        }
    }

    /**
     * @param $event
     */
    public function onPostFlush($event)
    {
        var_dump('onPostFlush');
//        var_dump($event);

        foreach ($this->changesRequests as $key=>$change) {
            $result = $change['qb']->getQuery()->execute();
            var_dump($result);
        }

        // es push
    }
}