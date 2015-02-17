<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 17/02/2015
 * Time: 14:30
 */

namespace Devyn\Component\ESIndexing;

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
     * @param string $type
     * @param array $index
     */
    function __construct($index)
    {
        $this->index = $index;
        $this->guessIndexingRequests();

        var_dump($this->getRequest('person', 'fullName'));
    }

    public function getRequest($class, $property = '')
    {
        if (empty($property)) {
            if (isset($this->requests[$class]['guessResourceType'])) {
                return $this->requests[$class]['guessResourceType'];
            }
            throw new \Exception('No matching found for ' . $class);
        }

        if (isset($this->requests[$class]['guessIndexingRequest'][$property])) {
            return $this->requests[$class]['guessIndexingRequest'][$property];
        }

        throw new \Exception('No matching found for ' . $class . ' and ' . $property);
    }

    protected function guessIndexingRequests()
    {
        foreach ($this->index as $typeName => $settings) {
            $this->requests[$typeName]['class'] = $settings['class'];
            $this->requests[$typeName]['frame'] = $settings['frame'];
            $this->requests[$typeName]['guessResourceType'] = 'guessResourceType for ' . $settings['class'];
            foreach ($settings['properties'] as $key => $property) {
                $this->requests[$typeName]['guessIndexingRequest'][$key] = 'guessIndexingRequest for ' . $settings['class'] . ':' . $key;
            }
        }

        var_dump($this->requests);
    }
}