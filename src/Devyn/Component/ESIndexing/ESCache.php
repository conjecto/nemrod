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

    public function getRequest($index, $uri, $type, $property = '')
    {
        if (empty($property)) {
            if (isset($this->requests[$index][$type]['guessResourceType'])) {
                return $this->requests[$index][$type]['guessResourceType']->andWhere('?s ?p ' . $uri)->getSparqlQuery();
            }
            throw new \Exception('No matching found for ' . $type);
        }

        if (isset($this->requests[$index][$type]['guessIndexingRequest'][$property])) {
            return $this->requests[$index][$type]['guessIndexingRequest'][$property]->andWhere('?s ?p ' . $uri)->getSparqlQuery();
        }

        throw new \Exception('No matching found for ' . $type . ' and ' . $property);
    }

    public function getFramePart($index, $type, $part)
    {
        $frame = $this->requests[$index][$type]['frame'];
        $framePart = substr($frame, strpos($frame, $part) + strlen($part));

        $chars = str_split($framePart);
        $paren_num = 0;
        $first = true;
        $result = '';

        foreach ($chars as $char) {
            if($char == '{') {
                $first = false;
                $paren_num++;
            }
            else if ($char == '}') {
                $paren_num--;
            }
            else if (!$first) {
                $result .= $char;
            }
            if ($paren_num == 0 && !$first) {
                break;
            }
        }

        return $result;
    }

    public function parse($query)
    {
        $string = substr($query, strpos($query, '@context') + 8);
        $paren_num = 0;
        $chars = str_split($query);

        $new_string = '';
        foreach ($chars as $char) {
                if($char == '{') {
                $paren_num++;
            }
            else if ($char == '}') {
                $paren_num--;
            }
            else if ($paren_num == 0) {
                $new_string .= $char;
            }
        }

        $new_string = trim($new_string);

        $string = substr($query, 0, strpos($query, '@context')) . ' ' . $new_string;

        var_dump($string);

        $query = str_replace('@id', '_id', $query);
        $query = str_replace('@type', '_type', $query);

        return $query;
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

        foreach ($this->index['indexes'] as $index => $types) {
            foreach ($types['types'] as $type => $settings) {
                if (!isset($settings['class']) || empty($settings['class'])) {
                    throw new \Exception('You have to specify a class for ' . $type);
                }
                if (!isset($settings['frame']) || empty($settings['frame'])) {
                    throw new \Exception('You have to specify a frame for ' . $type);
                }
                if (!isset($settings['properties']) || empty($settings['properties'])) {
                    throw new \Exception('You have to specify properties for ' . $type);
                }

                $this->requests[$index][$type]['class'] = $settings['class'];
                $this->requests[$index][$type]['frame'] = $settings['frame'];
                $_qb = clone $qb;
                $_qb->reset();
                $_qb->construct('?s ?p ' . $settings['class'])->where('?s a ' . $settings['class']);
                $this->requests[$index][$type]['guessResourceType'] = $_qb;

                foreach ($settings['properties'] as $property => $values) {
                    $_qb = clone $qb;
                    $_qb->reset();
                    $_qb->construct('?s ?p ' . $property)->where('?s a ' . $property);
                    $this->requests[$index][$type]['guessIndexingRequest'][$property] = $_qb;
                }
            }
        }
    }
}