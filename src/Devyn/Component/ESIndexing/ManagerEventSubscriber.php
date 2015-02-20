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
use EasyRdf\Serialiser\JsonLd;
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
        $this->changesRequests = [];
        foreach ($event->getChanges() as $key => $change) {
            foreach (['insert', 'delete'] as $action) {
                $properties = array();
                foreach ($change[$action] as $keyType => $actionType) {
                    if (!in_array($keyType, $properties)) {
                        $properties[] = $keyType;
                    }
                }
                $this->changesRequests[$key]['type'] = $change['type'];
                $this->changesRequests[$key]['properties'] = $properties;
            }
        }
//        $index = $this->typeRegistry->getType($change['type'])->getIndex()->getName();
//        $this->esCache->isPropertyTypeExist($index, $change['type'], $properties)
    }

    /**
     * @param $event
     */
    public function onPostFlush($event)
    {
        $qb = $this->esCache->getRm()->getQueryBuilder();
        $qb->reset();
        $qb->construct("?uri a ?t")->where("?uri a ?t");
        $uris = '';

        foreach ($this->changesRequests as $uri => $infos) {
            $uris .= ' <' . $uri . '>';
        }

        if (empty($uris)) {
            return;
        }

        $qb->value('?uri', $uris);
        $result = $qb->getQuery()->execute();
        $jsonLdSerializer = new JsonLd();

        foreach ($this->changesRequests as $uri => $infos) {
            $types = $result->all($uri, 'rdf:type');
            $newTypes = array();

            foreach ($types as $type) {
                $newType = RdfNamespace::shorten($type->getUri());
                $newTypes[] = $newType;

                $index = $this->typeRegistry->getType($newType);
                if ($index != null) {
                    $index = $index->getIndex()->getName();
                }

                if ($index && $this->esCache->isTypeIndexed($index, $newType, $infos['properties'])) {
                    $graph = $this->esCache->getRequest($index, $uri, $newType)->getQuery()->execute();
                    $jsonLd = $jsonLdSerializer->serialise($graph, 'jsonld', ['context' => $this->esCache->getTypeContext($index, $newType), 'frame' => $this->esCache->getTypeFrame($index, $newType)]);
                    $graph = json_decode($jsonLd, true)['@graph'][0];
                    $json = json_encode($graph);
                    $json = str_replace('@id', '_id', $json);
                    $json = str_replace('@type', '_type', $json);
                    // es push json
                }
            }

            if (array_key_exists($uri, $this->changesRequests)) {
                $oldType = $this->changesRequests[$uri]['type'];
                if (!in_array($oldType, $newTypes)) {
                    // es delete old type
                }
            }
        }
    }
}