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

use \Conjecto\Nemrod\Framing\Loader\JsonLdFrameLoader as BaseJsonLdFrameLoader;
use EasyRdf\TypeMapper;
use Conjecto\Nemrod\ResourceManager\FiliationBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JsonLdFrameLoader extends BaseJsonLdFrameLoader
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $esIndex;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
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
        $serializerHelper = $this->container->get('nemrod.elastica.serializer_helper');
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
            $frame = $serializerHelper->getTypeFramePath($this->esIndex, $parentClass);
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
    public function load($name, $type = null, $includeSubFrames = true, $assoc = true, $findTypeInFrames = false)
    {
        $frame = parent::load($name, $type, $includeSubFrames, $assoc, $findTypeInFrames);
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