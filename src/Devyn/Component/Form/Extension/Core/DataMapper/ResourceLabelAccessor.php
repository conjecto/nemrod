<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 26/01/2015
 * Time: 16:37
 */

namespace Devyn\Component\Form\Extension\Core\DataMapper;


use EasyRdf\Literal;
use Symfony\Component\PropertyAccess\Exception;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ResourceLabelAccessor  implements PropertyAccessorInterface
{
    /**
     * Sets the value at the end of the property path of the object
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
     * @param object|array $objectOrArray The object or array to modify
     * @param string|PropertyPathInterface $propertyPath The property path to modify
     * @param mixed $value The value to set at the end of the property path
     *
     * @throws Exception\NoSuchPropertyException If a property does not exist or is not public.
     * @throws Exception\UnexpectedTypeException If a value within the path is neither object
     *                                           nor array
     */
    public function setValue(&$objectOrArray, $propertyPath, $value)
    {
        return null;
    }

    /**
     * Returns the value at the end of the property path of the object
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
     * @param string|PropertyPathInterface $propertyPath The property path to read
     *
     * @return mixed The value at the end of the property path
     *
     * @throws Exception\NoSuchPropertyException If a property does not exist or is not public.
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        $value = $objectOrArray->get((string)$propertyPath);

        if ($value == null) {
            $value = $objectOrArray->label();
        }

        if ($value instanceof Literal) {
            return $value->getValue();
        }

        if ($value == null) {
            $value = $objectOrArray->getUri();
        }

        return $value;
    }
} 