<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ElasticSearch;

use Conjecto\Nemrod\ResourceManager\Event\Events;
use EasyRdf\RdfNamespace;
use EasyRdf\Serialiser\JsonLd;
use Elastica\Document;
use Elastica\Type;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ManagerEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var SerializerHelper
     */
    protected $esCache;

    /**
     * @var TypeRegistry
     */
    protected $typeRegistry;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $changesRequests;

    public function __construct(SerializerHelper $esCache, TypeRegistry $typeRegistry, Container $container)
    {
        $this->esCache = $esCache;
        $this->typeRegistry = $typeRegistry;
        $this->container = $container;
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
            Events::PostFlush => 'onPostFlush',
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
    }

    /**
     * @param $event
     *
     * @todo do it work !
     */
    public function onPostFlush($event)
    {
        return;
        $resourceToDocumentTransformer = new ResourceToDocumentTransformer($this->esCache, $this->typeRegistry, $this->container->get('nemrod.type_mapper'));
        $qb = $event->getRm()->getQueryBuilder();
        $qb->reset();
        $qb->construct("?uri a ?t")->where("?uri a ?t");
        $uris = '';

        if (empty($this->changesRequests)) {
            return;
        }

        foreach ($this->changesRequests as $uri => $infos) {
            $uris .= ' <'.$uri.'>';
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
                    /*
                     * @var Type
                     */
                    $esType = $this->container->get('nemrod.elastica.type.'.$index.'.'.$this->esCache->getTypeName($index, $newType));
                    $document = $resourceToDocumentTransformer->transform($uri, $newType);
                    if ($document) {
                        $esType->addDocument($document);
                    }
                }
            }

            if (array_key_exists($uri, $this->changesRequests)) {
                $oldType = $this->changesRequests[$uri]['type'];
                if (!in_array($oldType, $newTypes)) {
                    $index = $this->typeRegistry->getType($oldType);
                    if ($index != null) {
                        $index = $index->getIndex()->getName();
                        /*
                         * @var Type
                         */
                        $esType = $this->container->get('nemrod.elastica.type.'.$index.'.'.$this->esCache->getTypeName($index, $oldType));
                        $esType->deleteDocument(new Document($uri, array(), $oldType, $index));
                    }
                }
            }
        }
    }
}
