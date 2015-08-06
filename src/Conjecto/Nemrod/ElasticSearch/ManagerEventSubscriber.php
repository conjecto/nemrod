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
use Conjecto\Nemrod\ResourceManager\FiliationBuilder;
use EasyRdf\RdfNamespace;
use Elastica\Document;
use Elastica\Type;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ManagerEventSubscriber implements EventSubscriberInterface
{
    /** @var SerializerHelper */
    protected $serializerHelper;

    /** @var  ConfigManager */
    protected $configManager;

    /** @var IndexRegistry */
    protected $indexRegistry;

    /** @var Container */
    protected $container;

    /** @var CascadeUpdate */
    protected $cascadeUpdateSearch;

    /** @var array */
    protected $changesRequests;

    /** @var FiliationBuilder */
    protected $filiationBuilder;

    /** @var array */
    protected $arrayResourcesToUpdateAfterDeletion;

    /** @var ResourceToDocumentTransformer */
    protected $resourceToDocumentTransformer;

    /**
     * @param SerializerHelper $serializerHelper
     * @param IndexRegistry $indexRegistry
     * @param ConfigManager $configManager
     * @param FiliationBuilder $filiationBuilder
     * @param Container $container
     */
    public function __construct(SerializerHelper $serializerHelper, ConfigManager $configManager, IndexRegistry $indexRegistry, FiliationBuilder $filiationBuilder, Container $container)
    {
        $this->serializerHelper = $serializerHelper;
        $this->configManager = $configManager;
        $this->indexRegistry = $indexRegistry;
        $this->container = $container;
        $this->filiationBuilder = $filiationBuilder;
        $this->cascadeUpdateHelper = new CascadeUpdateHelper($this->serializerHelper, $this->configManager, $this->indexRegistry, $this->container);
        $this->resourceToDocumentTransformer = new ResourceToDocumentTransformer(
            $this->serializerHelper,
            $this->configManager,
            $this->container->get('nemrod.type_mapper'),
            $this->container->get('nemrod.elastica.jsonld.serializer')
        );
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
                if ($change[$action] === 'all' && $action === 'delete') {
                    $arrayResourcesDeleted[$key] = $change['type'];
                    $this->changesRequests[$key]['delete'] = true;
                }
                else {
                    $this->changesRequests[$key]['delete'] = false;
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

        $this->arrayResourcesToUpdateAfterDeletion = $this->cascadeUpdateHelper->searchResourcesToCascadeRemove($arrayResourcesDeleted, $this->filiationBuilder, $event->getRm());
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

        $result = $this->getResourcesToUpdate($event->getRm());
        $resourcesModified = $this->updateDocuments($result);
        $this->cascadeUpdateDocuments($resourcesModified, $event->getRm());
        $this->cascadeRemoveDocuments();
    }

    /**
     * Get the uri resources than have been changed
     * @param $rm
     * @return mixed
     */
    protected function getResourcesToUpdate($rm)
    {
        $qb = $rm->getQueryBuilder();
        $qb->reset()
            ->construct('?uri a ?t')
            ->addConstruct('?uri rdf:type ?type')
            ->where('?uri a ?t')
            ->andWhere('?uri rdf:type ?type');

        // set all resource uris
        $uris = '';
        foreach ($this->changesRequests as $uri => $infos) {
            $uris .= ' <'.$uri.'>';
        }
        $qb->value('?uri', $uris);

        return $qb->getQuery()->execute();
    }

    /**
     * Foreach changed resource, update the ES document
     * @param $result
     * @return array $resourcesModified
     * @throws \Exception
     */
    protected function updateDocuments($result)
    {
        $resourcesModified = array();
        // foreach resources modified
        foreach ($this->changesRequests as $uri => $infos) {
            $types = $result->all($uri, 'rdf:type');
            $this->deleteOldDocument($uri, $types, $this->changesRequests[$uri]['type']);
            if (!$infos['delete']) {
                $mostAccurateType = $this->updateDocument($uri, $types, $this->resourceToDocumentTransformer);
                $resourcesModified[$uri] = $mostAccurateType;
            }
        }

        return $resourcesModified;
    }

    /**
     * @param $uri
     * @param $types
     * @param $trans
     * @return null
     * @throws \Exception
     */
    protected function updateDocument($uri, $types, $trans)
    {
        $mostAccurateTypes = $this->filiationBuilder->getMostAccurateType($types, $this->serializerHelper->getAllTypes());
        $mostAccurateType = null;
        // not specified in project ontology description
        if (count($mostAccurateTypes) == 1) {
            $mostAccurateType = $mostAccurateTypes[0];
        } else {
//            echo "Seems to not have to be indexed";
        }

        $typesConfig = $this->configManager->getTypesConfigurationByClass($mostAccurateType);
        foreach($typesConfig as $typeConfig) {
            $indexConfig = $typeConfig->getIndex();
            $index = $indexConfig->getName();
            $this->container->get('nemrod.elastica.jsonld.frame.loader')->setEsIndex($index);
            $esType = $this->indexRegistry->getIndex($index)->getType($typeConfig->getType());
            $document = $trans->transform($uri, $index, $mostAccurateType);
            if ($document) {
                $esType->addDocument($document);
            }
        }

        return $mostAccurateType;
    }

    /**
     * If a types resource has changed and the new type is mapped to another document type, then the old document is removed
     * @param $uri
     * @param $types
     * @param $oldType
     * @return bool
     */
    protected function deleteOldDocument($uri, $types, $oldType)
    {
        $newTypes = array();
        foreach ($types as $type) {
            $type = (string)$type;
            if ($type && !empty($type)) {
                $newTypes[] = RdfNamespace::shorten($type);
            }
        }

        if (!in_array($oldType, $newTypes)) {
            $typesConfig = $this->configManager->getTypesConfigurationByClass($oldType);
            foreach($typesConfig as $typeConfig) {
                $indexConfig = $typeConfig->getIndex();
                $index = $indexConfig->getName();
                $this->container->get('nemrod.elastica.jsonld.frame.loader')->setEsIndex($index);
                if ($index !== null) {
                    $esType = $this->indexRegistry->getIndex($index)->getType($typeConfig->getType());
                    // Trow an exeption if document does not exist
                    try {
                        $esType->deleteDocument(new Document($uri, array(), $oldType, $indexConfig->getElasticSearchName()));
                        return true;
                    } catch (\Exception $e) {
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $resourcesModified
     * @param $rm
     */
    protected function cascadeUpdateDocuments($resourcesModified, $rm)
    {
        // cascade update
        foreach ($resourcesModified as $uri => $type) {
            $this->cascadeUpdateHelper->cascadeUpdate($uri, $type, $this->changesRequests[$uri]['properties'], $this->filiationBuilder, $this->resourceToDocumentTransformer, $rm, $resourcesModified);
        }
    }

    /**
     * @throws \Exception
     */
    protected function cascadeRemoveDocuments()
    {
        // cascade remove
        foreach ($this->arrayResourcesToUpdateAfterDeletion as $uri => $type) {
            $typesConfig = $this->configManager->getTypesConfigurationByClass($type);
            foreach($typesConfig as $typeConfig) {
                $indexConfig = $typeConfig->getIndex();
                $index = $indexConfig->getName();
                $this->container->get('nemrod.elastica.jsonld.frame.loader')->setEsIndex($index);
                if ($index !== null) {
                    $this->cascadeUpdateHelper->updateDocument($uri, $type, $this->filiationBuilder, $this->resourceToDocumentTransformer);
                }
            }
        }
    }
}
