<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Form\Extension\Core\DataMapper;

use EasyRdf\Literal;
use Symfony\Component\PropertyAccess\Exception;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ResourceLabelAccessor  implements PropertyAccessorInterface
{
    /**
     * Sets the value at the end of the property path of the object.
     *
     * Example:
     *
     *     use Symfony\Component\PropertyAccess\PropertyAccess;
     *
     *     $propertyAccessor = PropertyAccess::getPropertyAccessor();
     *
     *     echo $propertyAccessor->setValue($object, 'child.name', 'Fabien');
     *     // equals echo $object->getChild()->setName('Fabien');
     *
     * This method first tries to find a public setter for each property in the
     * path. The name of the setter must be the camel-cased property name
     * prefixed with "set".
     *
     * If the setter does not exist, this method tries to find a public
     * property. The value of the property is then changed.
     *
     * If neither is found, an exception is thrown.
     *
     * @param object|array                 $objectOrArray The object or array to modify
     * @param string|PropertyPathInterface $propertyPath  The property path to modify
     * @param mixed                        $value         The value to set at the end of the property path
     *
     * @throws Exception\NoSuchPropertyException If a property does not exist or is not public.
     * @throws Exception\UnexpectedTypeException If a value within the path is neither object
     *                                           nor array
     */
    public function setValue(&$objectOrArray, $propertyPath, $value)
    {
        return;
    }

    /**
     * Returns the value at the end of the property path of the object.
     *
     * Example:
     *
     *     use Symfony\Component\PropertyAccess\PropertyAccess;
     *
     *     $propertyAccessor = PropertyAccess::getPropertyAccessor();
     *
     *     echo $propertyAccessor->getValue($object, 'child.name);
     *     // equals echo $object->getChild()->getName();
     *
     * This method first tries to find a public getter for each property in the
     * path. The name of the getter must be the camel-cased property name
     * prefixed with "get", "is", or "has".
     *
     * If the getter does not exist, this method tries to find a public
     * property. The value of the property is then returned.
     *
     * If none of them are found, an exception is thrown.
     *
     * @param object|array $objectOrArray The object or array to traverse
     * @param string|PropertyPathInterface $propertyPath  The property path to read
     *
     * @return mixed The value at the end of the property path
     *
     * @throws Exception\NoSuchPropertyException If a property does not exist or is not public.
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        $value = null;

        if ($objectOrArray instanceof Resource) {
            $value = $objectOrArray->get((string) $propertyPath);

            if ($value === null) {
                $value = $objectOrArray->label();
            }
        }
        else if (is_array($objectOrArray)) {
            $value = isset($objectOrArray[(string) $propertyPath]) ? $objectOrArray[(string) $propertyPath] : null;

            if ($value === null) {
                foreach (array('skos:prefLabel', 'rdfs:label', 'foaf:name', 'rss:title', 'dc:title', 'dc11:title') as $labelProperty) {
                    $value = isset($objectOrArray[$labelProperty]) ? $objectOrArray[$labelProperty] : null;
                    if ($value) {
                        break;
                    }
                }
            }
        }

        if ($value instanceof Literal) {
            return $value->getValue();
        }

        else if ($value instanceof Resource) {
            return $value->getUri();
        }

        if ($objectOrArray instanceof Resource) {
            $value = $objectOrArray->getUri();
        }
        else if (is_array($objectOrArray)) {
            $value = isset($objectOrArray['uri']) ? $objectOrArray['uri'] : null;
        }

        return $value;
    }

    /**
     * Returns whether a value can be written at a given property path.
     *
     * Whenever this method returns true, {@link setValue()} is guaranteed not
     * to throw an exception when called with the same arguments.
     *
     * @param object|array                 $objectOrArray The object or array to check
     * @param string|PropertyPathInterface $propertyPath  The property path to check
     *
     * @return bool Whether the value can be set
     *
     * @throws Exception\InvalidArgumentException If the property path is invalid
     */
    public function isWritable($objectOrArray, $propertyPath)
    {
        return true;
    }

    /**
     * Returns whether a property path can be read from an object graph.
     *
     * Whenever this method returns true, {@link getValue()} is guaranteed not
     * to throw an exception when called with the same arguments.
     *
     * @param object|array                 $objectOrArray The object or array to check
     * @param string|PropertyPathInterface $propertyPath  The property path to check
     *
     * @return bool Whether the property path can be read
     *
     * @throws Exception\InvalidArgumentException If the property path is invalid
     */
    public function isReadable($objectOrArray, $propertyPath)
    {
        return true;
    }
}
