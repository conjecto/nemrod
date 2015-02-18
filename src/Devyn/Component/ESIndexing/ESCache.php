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
     * @var \Devyn\Component\QueryBuilder\QueryBuilder
     */
    protected $qb;

    /**
     * @param string $type
     * @param array $index
     */
    function __construct(Manager $rm, $index)
    {
        $this->rm = $rm;
        $this->index = $index;
        $this->guessIndexingRequests();
        $this->qb = $this->rm->getQueryBuilder();
    }

    public function getRequest($index, $uri, $type, $property = '')
    {
        if (empty($property)) {
            if (isset($this->requests[$index][$type]['guessTypeRequest'])) {
                return $this->requests[$index][$type]['guessTypeRequest']->bind('?s', $uri)->getSparqlQuery();
            }
            throw new \Exception('No matching found for index ' . $index . ' and type ' . $type);
        }

        if (isset($this->requests[$index][$type]['properties'][$property]['guessPropertyRequest'])) {
            return $this->requests[$index][$type]['properties'][$property]['guessPropertyRequest']->bind('?s', $uri)->getSparqlQuery();
        }

        throw new \Exception('No matching found for index ' . $index . ' and type ' . $type . ' and property ' . $property);
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
        foreach ($this->index['indexes'] as $index => $types) {
            foreach ($types['types'] as $type => $settings) {
                if (!isset($settings['type']) || empty($settings['type'])) {
                    throw new \Exception('You have to specify a type for ' . $type);
                }
                if (!isset($settings['frame']) || empty($settings['frame'])) {
                    throw new \Exception('You have to specify a frame for ' . $type);
                }
                if (!isset($settings['properties']) || empty($settings['properties'])) {
                    throw new \Exception('You have to specify properties for ' . $type);
                }

                $this->requests[$index][$type]['type'] = $settings['type'];
                $this->requests[$index][$type]['frame'] = $this->getPropertyFrame($settings['frame'], $settings['type']);
                $this->requests[$index][$type]['guessTypeRequest'] = $this->getTypeRequest($settings['type']);

                foreach ($settings['properties'] as $property => $values) {
                    $propertyFrame = $this->getPropertyFrame($this->requests[$index][$type]['frame'], $property);
                    $this->requests[$index][$type]['properties'][$property]['frame'] = $propertyFrame;
                    $this->requests[$index][$type]['properties'][$property]['guessPropertyRequest'] = $this->getPropertyRequest($this->getPropertyFromFrame($propertyFrame));
                }
            }
        }
    }

    protected function getPropertyFromFrame($propertyFrame)
    {
        $propertyFrame = strstr($propertyFrame, '{', true);
        $propertyFrame = $this->strrstr($propertyFrame, ':', true);
        $propertyFrame = str_replace('"', '', $propertyFrame);

        return $propertyFrame;
    }

    protected function getTypeRequest($type)
    {
        $qb = clone $this->rm->getQueryBuilder();
        $qb->construct('?s ?p ' . $type)->where('?s a ' . $type);
        return $qb;
    }

    protected function getPropertyRequest($property)
    {
        $qb = clone $this->rm->getQueryBuilder();
        $qb->construct('?s ?p ' . $property)->where('?s a ' . $property);
        return $qb;
    }

    protected function strrstr($h, $n, $before = false) {
        $rpos = strrpos($h, $n);
        if ($rpos === false)
            return false;
        if ($before == false)
            return substr($h, $rpos);
        else
            return substr($h, 0, $rpos);
    }

    protected function getPrefix($frame, $cutFrame)
    {
        $beginFrame = strstr($frame, $cutFrame, true);
        $lastType = $this->strrstr($beginFrame, '@type');
        $lastType = $this->strrstr($lastType, '"');
        return $lastType;
    }

    protected function getPropertyFrame($frame, $property)
    {
        $cutFrame = strstr($frame, $property);
        return $this->getPrefix($frame, $cutFrame) . $this->getParenthesis($cutFrame, true);
    }

    protected function getFramePart($index, $type, $part, $include = false)
    {
        $frame = $this->requests[$index][$type]['frame'];
        $framePart = substr($frame, strpos($frame, $part) + strlen($part));

        $result = $this->getParenthesis($framePart, $include);

        return $result;
    }

    protected function getParenthesis($framePart, $include = false, $part = '')
    {
        $chars = str_split($framePart);
        $paren_num = 0;
        $first = true;
        $result = '';

        foreach ($chars as $char) {
            if($char == '{') {
                if ($include) {
                    $result .= $char;
                }
                $first = false;
                $paren_num++;
            }
            else if ($char == '}') {
                if ($include) {
                    $result .= $char;
                }
                $paren_num--;
            }
            else if (!$first) {
                $result .= $char;
            }
            if ($paren_num == 0 && !$first) {
                break;
            }
            else if ($include && $first) {
                $result .= $char;
            }
        }

        if ($include) {
            $result = $part . $result;
        }

        return $result;
    }

    protected function parse($index, $type)
    {
        $query = $this->requests[$index][$type]['frame'];
        $frame = '"' . $this->getFramePart('ogbd', 'person', '@context', true) . ',';
        $query = str_replace($frame, '', $query);
        $query = str_replace('@id', '_id', $query);
        $query = str_replace('@type', '_type', $query);

        return $query;
    }
}