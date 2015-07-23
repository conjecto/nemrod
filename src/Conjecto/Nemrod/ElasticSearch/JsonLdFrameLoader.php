<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 23/07/2015
 * Time: 11:03
 */

namespace Conjecto\Nemrod\ElasticSearch;

use \Conjecto\Nemrod\Framing\Loader\JsonLdFrameLoader as BaseJsonLdFrameLoader;
use EasyRdf\TypeMapper;

class JsonLdFrameLoader extends BaseJsonLdFrameLoader
{
    /**
     * @var SerializerHelper
     */
    protected $serializerHelper;

    /**
     * @var string
     */
    protected $esIndex;

    /**
     * @param $serializerHelper
     */
    public function setSerializerHelper($serializerHelper)
    {
        $this->serializerHelper = $serializerHelper;
    }

    /**
     * @param $esIndex
     */
    public function setEsIndex($esIndex)
    {
        $this->esIndex = $esIndex;
    }

    /**
     * Search parent frame pathes
     * @param $parentClass
     * @param array $parentFrames
     * @return array
     */
    public function getParentMetadatas($parentClass, $parentFrames = array(), $skipRoot = false)
    {
        if (!$this->esIndex) {
            throw new \Exception('Elasticsearch index is not defined');
        }

        if (!$parentClass) {
            return $parentFrames;
        }

        // if we don't want to get the root frame
        if (!$skipRoot) {
            $parentFrames[] = $this->serializerHelper->getTypeFramePath($this->esIndex, $parentClass);
        }

        $phpClass = TypeMapper::get($parentClass);
        if ($phpClass) {
            $metadata = $this->metadataFactory->getMetadataForClass($phpClass);
            return $this->getParentMetadatas($metadata->getParentClass(), $parentFrames);
        }
        else {
            return $parentFrames;
        }
    }

    /**
     * @param $name
     * @param null $parentClass
     * @param bool $includeSubFrames
     * @param bool $assoc
     * @param bool $getTypeFromFrame
     */
    public function load($name, $parentClass = null, $includeSubFrames = true, $assoc = true, $getTypeFromFrame = false)
    {
        $frame = parent::load($name, $parentClass = null, $includeSubFrames = true, $assoc = true, $getTypeFromFrame = false);
        return $this->addMissingProperties($frame);
    }

    protected function addMissingProperties($frame)
    {
        $missingProperties = [
            'rdf:type' => []
        ];

        foreach ($missingProperties as $key => $value) {
            if (!isset($frame[$key])) {
                $frame[$key] = $value;
            }
            else {
                $frame[$key] = array_merge_recursive($value, $frame[$key]);
            }
        }

        return $frame;
    }
}