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
use Elastica\Document;
use Elastica\Type;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ManagerEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var SerializerHelper
     */
    protected $serializerHelper;

    /**
     * @var TypeRegistry
     */
    protected $typeRegistry;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var CascadeUpdate
     */
    protected $cascadeUpdateSearch;

    /**
     * @var array
     */
    protected $changesRequests;

    /**
     * @var
     */
    protected $arrayResourcesToUpdateAfterDeletion;

    /**
     * @param SerializerHelper $serializerHelper
     * @param TypeRegistry     $typeRegistry
     * @param Container        $container
     */
    public function __construct(SerializerHelper $serializerHelper, TypeRegistry $typeRegistry, Container $container)
    {
        $this->serializerHelper = $serializerHelper;
        $this->typeRegistry = $typeRegistry;
        $this->container = $container;
        $this->cascadeUpdateHelper = new CascadeUpdateHelper($this->serializerHelper, $this->container);
    }

    /**
     * Subscribe to events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PreFlush => 'onPreFlush',
            Events::PostFlush => 'onPostFlush',
        );
    }

    /**
     * Called when preFlush event is received
     * We get all resources has been modified into changeRequests
     * We get all properties has been modified by resource
     * We get all resources wich have to be updated in ES after a mapped resource has been deleted
     * If event->changes[type][delete] = all so this resource will be deleted in the triple store.
     *
     * @param $event
     */
    public function onPreFlush($event)
    {
        $arrayResourcesDeleted = array();
        $this->changesRequests = array();

        foreach ($event->getChanges() as $key => $change) {
            foreach (['insert', 'delete'] as $action) {
                if ($change[$action] == 'all' && $action == 'delete') {
                    $arrayResourcesDeleted[$key] = $change['type'];
                }
                if (isset($this->changesRequests[$key]['properties'])) {
                    $properties = $this->changesRequests[$key]['properties'];
                } else {
                    $properties = array();
                }
                if (is_array($change[$action])) {
                    foreach ($change[$action] as $keyType => $actionType) {
                        if (!in_array($keyType, $properties)) {
                            $properties[] = $keyType;
                        }
                    }
                }
                $this->changesRequests[$key]['type'] = $change['type'];
                $this->changesRequests[$key]['properties'] = $properties;
            }
        }

        $this->arrayResourcesToUpdateAfterDeletion = $this->cascadeUpdateHelper->searchResourcesToCascadeRemove($arrayResourcesDeleted, $event->getRm());
    }

    /**
     * Called when postFlush event is received
     * We get all types of all resources modified
     * If a type is mapped, we update the resource in ES
     * If a resource have been change of type and its old type was mapped, the ES document related is removed
     * At the end, we make a cascade update of the modified resources, and a cascade remove if needed.
     *
     * @param $event
     */
    public function onPostFlush($event)
    {
        if (empty($this->changesRequests)) {
            return;
        }

        $resourceToDocumentTransformer = new ResourceToDocumentTransformer(
            $this->serializerHelper, $this->typeRegistry, $this->container->get('nemrod.type_mapper'), $this->container->get('nemrod.jsonld.serializer')
        );

        $qb = $event->getRm()->getQueryBuilder();
        $qb->reset();
        $qb->construct('?uri a ?t')->where('?uri a ?t');
        $uris = '';

        foreach ($this->changesRequests as $uri => $infos) {
            $uris .= ' <'.$uri.'>';
        }

        $qb->value('?uri', $uris);
        $result = $qb->getQuery()->execute();
        $resourcesModified = array();

        // foreach resources modified
        foreach ($this->changesRequests as $uri => $infos) {
            $types = $result->all($uri, 'rdf:type');
            $newTypes = array();

            // check all resource type to check if this type is indexed in ES
            foreach ($types as $type) {
                $newType = RdfNamespace::shorten($type->getUri());
                $newTypes[] = $newType;

                $index = $this->typeRegistry->getType($newType);
                if ($index != null) {
                    $index = $index->getIndex()->getName();
                }

                // update the ES document
                if ($index && $this->serializerHelper->isTypeIndexed($index, $newType, $infos['properties'])) {
                    /*
                     * @var Type
                     **/
                    $esType = $this->container->get('nemrod.elastica.type.'.$index.'.'.$this->serializerHelper->getTypeName($index, $newType));
                    $document = $resourceToDocumentTransformer->transform($uri, $newType);
                    if ($document) {
                        $resourcesModified[$uri][] = $newType;
                        $esType->addDocument($document);
                    }
                }
            }

            $oldType = $this->changesRequests[$uri]['type'];
            if (!in_array($oldType, $newTypes)) {
                $index = $this->typeRegistry->getType($oldType);
                if ($index != null) {
                    $index = $index->getIndex()->getName();
                    /*
                     * @var Type
                     **/
                    $esType = $this->container->get('nemrod.elastica.type.'.$index.'.'.$this->serializerHelper->getTypeName($index, $oldType));

                    // Trow an exeption if document does not exist
                    try {
                        $esType->deleteDocument(new Document($uri, array(), $oldType, $index));
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        // cascade update
        foreach ($resourcesModified as $uri => $types) {
            foreach ($types as $type) {
                $this->cascadeUpdateHelper->cascadeUpdate($uri, $type, $this->changesRequests[$uri]['properties'], $resourceToDocumentTransformer, $event->getRm(), $resourcesModified);
            }
        }

        // cascade remove
        foreach ($this->arrayResourcesToUpdateAfterDeletion as $uri => $type) {
            $index = $this->typeRegistry->getType($type);
            if ($index != null) {
                $index = $index->getIndex()->getName();
                $this->cascadeUpdateHelper->updateDocument($uri, $type, $index, $resourceToDocumentTransformer);
            }
        }
    }
}
