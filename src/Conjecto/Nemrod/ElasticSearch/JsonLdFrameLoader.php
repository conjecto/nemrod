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
use Conjecto\Nemrod\ResourceManager\FiliationBuilder;

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
     * @param SerializerHelper $serializerHelper
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
    public function getParentFrames($type)
    {
        if (!$type) {
            return array();
        }

        if (!$this->esIndex) {
            throw new \Exception('Elasticsearch index is not defined');
        }

        $parentClasses = $this->filiationBuilder->getParentTypes($type);
        $parentFrames = array();

        if (!$parentClasses || empty($parentClasses)) {
            return $parentFrames;
        }

        foreach ($parentClasses as $parentClass) {
            $frame = $this->serializerHelper->getTypeFramePath($this->esIndex, $parentClass);
            if ($frame) {
                $parentFrames[] = $frame;
            }
        }

        return $parentFrames;
    }

    /**
     * @param $name
     * @param null $parentClass
     * @param bool $includeSubFrames
     * @param bool $assoc
     * @param bool $getTypeFromFrame
     */
    public function load($name, $type = null, $includeSubFrames = true, $assoc = true)
    {
        $frame = parent::load($name, $type, $includeSubFrames, $assoc);
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