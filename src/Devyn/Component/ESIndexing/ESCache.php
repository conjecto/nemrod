<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 17/02/2015
 * Time: 14:30
 */

namespace Devyn\Component\ESIndexing;
use Devyn\Component\RAL\Manager\Manager;

/**
 * Class ESCache
 * @package Devyn\Component\ESIndexing
 */
class ESCache
{
    /**
     * @var array
     */
    protected $index;

    /**
     * @var array
     */
    protected $requests;

    /**
     * @var Manager
     */
    protected $rm;

    /**
     * @param string $type
     * @param array $index
     */
    function __construct(Manager $rm, $index)
    {
        $this->rm = $rm;
        $this->index = $index;
        $this->guessIndexingRequests();
    }

    public function getRequest($uri, $class, $property = '')
    {
        if (empty($property)) {
            if (isset($this->requests[$class]['guessResourceType'])) {
                return $this->requests[$class]['guessResourceType']->andWhere('?s ?p ' . $uri)->getSparqlQuery();
            }
            throw new \Exception('No matching found for ' . $class);
        }

        if (isset($this->requests[$class]['guessIndexingRequest'][$property])) {
            return $this->requests[$class]['guessIndexingRequest'][$property]->andWhere('?s ?p ' . $uri)->getSparqlQuery();
        }

        throw new \Exception('No matching found for ' . $class . ' and ' . $property);
    }

    /**
     * @return array
     */
    public function getRequests()
    {
        return $this->requests;
    }

    protected function guessIndexingRequests()
    {
        $qb = $this->rm->getQueryBuilder();

        foreach ($this->index as $typeName => $settings) {

            if (!isset($settings['class']) || empty($settings['class'])) {
                throw new \Exception('You have to specify a class for ' . $typeName);
            }
            if (!isset($settings['frame']) ||empty($settings['frame'])) {
                throw new \Exception('You have to specify a frame for ' . $typeName);
            }
            if (!isset($settings['properties']) || empty($settings['properties'])) {
                throw new \Exception('You have to specify properties for ' . $typeName);
            }

            $this->requests[$typeName]['class'] = $settings['class'];
            $this->requests[$typeName]['frame'] = $settings['frame'];
            $_qb = clone $qb;
            $_qb->reset();
            $_qb->construct('?s ?p ' . $settings['class'])->where('?s a ' . $settings['class']);
            $this->requests[$typeName]['guessResourceType'] = $_qb;

            foreach ($settings['properties'] as $key => $property) {
                $_qb = clone $qb;
                $_qb->reset();
                $_qb->construct('?s ?p ' . $key)->where('?s a ' . $key);
                $this->requests[$typeName]['guessIndexingRequest'][$key] = $_qb;
            }
        }
    }
}