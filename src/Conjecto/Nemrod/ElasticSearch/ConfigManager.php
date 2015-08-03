<?php

namespace Conjecto\Nemrod\ElasticSearch;
/**
 * Central manager for index and type configuration.
 */
/**
 * Class ConfigManager
 * @package Conjecto\Nemrod\Configuration
 */
class ConfigManager
{
    /**
     * @var IndexConfig[]
     */
    private $indexes = array();

    /**
     * @param $indexName
     * @param $config
     */
    public function setIndexConfigurationArray($indexName, $config) {
        $this->indexes[$indexName] =  new IndexConfig($config['name'], array(), array(
            'elasticSearchName' => $config['elasticsearch_name'],
            'settings' => $config['settings'],
            //'useAlias' => $config['use_alias'],
        ));
    }

    /**
     * @param $indexName
     * @param $config
     */
    public function setTypeConfigurationArray($indexName, $typeName, $config) {
        $index = $this->getIndexConfiguration($indexName);
        $index->addType($typeName, new TypeConfig(
            $typeName,
            $config['type'],
            $config['frame']
        ));
    }

    /**
     * @param $indexName
     * @return IndexConfig
     */
    public function getIndexConfiguration($indexName)
    {
        if (!$this->hasIndexConfiguration($indexName)) {
            throw new \InvalidArgumentException(sprintf('Index with name "%s" is not configured.', $indexName));
        }

        return $this->indexes[$indexName];
    }

    /**
     * @return array
     */
    public function getIndexNames()
    {
        return array_keys($this->indexes);
    }

    /**
     * @param $indexName
     * @param $typeName
     * @return TypeConfig
     */
    public function getTypeConfiguration($indexName, $typeName)
    {
        $index = $this->getIndexConfiguration($indexName);
        $type = $index->getType($typeName);

        if (!$type) {
            throw new \InvalidArgumentException(sprintf('Type with name "%s" on index "%s" is not configured', $typeName, $indexName));
        }

        return $type;
    }

    /**
     * @param $indexName
     * @return bool
     */
    public function hasIndexConfiguration($indexName)
    {
        return isset($this->indexes[$indexName]);
    }

    /**
     * Return all elastica types configured for a semantic class
     * @param $class
     * @return TypeConfig[]
     */
    public function getTypesConfigurationByClass($class)
    {
        $types = array();
        foreach($this->indexes as $key => $index) {
            foreach($index->getTypes() as $type) {
                if($type->getType() == $class) {
                    $types[] = $type;
                }
            }
        }
        return $types;
    }
}
