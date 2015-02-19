<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/02/2015
 * Time: 12:07
 */

namespace Devyn\Component\ESIndexing;


use Devyn\Bridge\Elastica\TypeRegistry;
use Devyn\Component\RAL\Manager\Event\Events;
use EasyRdf\RdfNamespace;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ManagerEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ESCache
     */
    protected $esCache;

    /**
     * @var TypeRegistry
     */
    protected $typeRegistry;

    /**
     * @var array
     */
    protected $changesRequests;

    function __construct(ESCache $esCache, TypeRegistry $typeRegistry)
    {
        $this->esCache = $esCache;
        $this->typeRegistry = $typeRegistry;
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
//        var_dump('onPreFlush');
//        var_dump($event);
        $this->changesRequests = [];
        foreach ($event->getChanges() as $key => $change) {
            $index = $this->typeRegistry->getType($change['type'])->getIndex()->getName();
            $properties = array();
            foreach (['insert', 'delete'] as $action) {
                foreach ($change[$action] as $keyType => $actionType) {
                    if (!in_array($keyType, $properties)) {
                        $properties[] = $keyType;
                    }
                }
            }

            if ($this->esCache->isPropertyTypeExist($index, $change['type'], $properties)) {
                if (!in_array($key, $this->changesRequests)) {
                    $this->changesRequests[$key] = $change['type'];
                }
            }
        }
    }

    /**
     * @param $event
     */
    public function onPostFlush($event)
    {
//        var_dump('onPostFlush');
//        var_dump($event);

        $qb = $this->esCache->getRm()->getQueryBuilder();
        $qb->reset();
        $qb->construct("?uri a ?t")->where("?uri a ?t");
        $uris = '';

        foreach ($event->getUris() as $uri) {
            $uris .= ' <' . $uri . '>';
        }

        $qb->value('?uri', $uris);
        $result = $qb->getQuery()->execute();

        foreach ($event->getUris() as $uri) {
            $types = $result->all($uri, 'rdf:type');
            $newTypes = array();

            foreach ($types as $type) {
                $newType = RdfNamespace::shorten($type->getUri());
                $newTypes[] = $newType;

                $index = $this->typeRegistry->getType($newType);
                if ($index != null) {
                    $index = $index->getIndex()->getName();
                }
                if ($index && $this->esCache->isTypeIndexed($index, $newType)) {
                    $_qb = $this->esCache->getRequest($index, $uri, $newType);
//                    var_dump($_qb->getSparqlQuery());
                    $result = $_qb->getQuery()->execute();
//                    var_dump($result);
                    // es push
                }
            }

            if (array_key_exists($uri, $this->changesRequests)) {
                $oldType = $this->changesRequests[$uri];
                if (!in_array($oldType, $newTypes)) {
                    // es delete old type
                }
            }
        }
    }
}