<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Devyn\Component\Form\Extension\Core\DataMapper;

use Devyn\Component\RdfNamespace\RdfNamespaceRegistry;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ResourcePropertyPathMapper implements DataMapperInterface
{
    /**
     * @var RdfNamespaceRegistry
     */
    private $nsRegistry;

    /**
     * @param RdfNamespaceRegistry $nsRegistry
     */
    public function __construct(RdfNamespaceRegistry $nsRegistry)
    {
        $this->nsRegistry = $nsRegistry;
    }

    /**
     * Maps properties of some data to a list of forms.
     *
     * @param mixed $data Structured data.
     * @param FormInterface[] $forms A list of {@link FormInterface} instances.
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
     */
    public function mapDataToForms($data, $forms)
    {
        $empty = null === $data || array() === $data;

        if (!$empty && !is_array($data) && !is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();
            if (!$empty && null !== $propertyPath && $config->getMapped()) {
                $values = $this->getValue($data, $propertyPath, $config);
                $form->setData($values);
            } else {
                $form->setData($form->getConfig()->getData());
            }
        }
    }

    /**
     * Maps the data of a list of forms into the properties of some data.
     *
     * @param FormInterface[] $forms A list of {@link FormInterface} instances.
     * @param mixed $data Structured data.
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
     */
    public function mapFormsToData($forms, &$data)
    {
        if (null === $data) {
            return;
        }

        if (!is_array($data) && !is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            // Write-back is disabled if the form is not synchronized (transformation failed),
            // if the form was not submitted and if the form is disabled (modification not allowed)
            if (null !== $propertyPath && $config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && !$form->isDisabled()) {

                // If the field is of type DateTime and the data is the same skip the update to
                // keep the original object hash
                if ($form->getData() instanceof \DateTime && $form->getData() == $this->propertyAccessor->getValue($data, $propertyPath)) {
                    continue;
                }

                // If the data is identical to the value in $data, we are
                // dealing with a reference
                if (!is_object($data) || !$config->getByReference() || $form->getData() !== $this->getValue($data, $propertyPath, $config)) {
                    $this->setValue($data, $propertyPath, $config, $form->getData());
                }
            }
        }
    }

    /**
     * @param $objectOrArray
     * @param $propertyPath
     * @param FormBuilder $formConfig
     * @return array|string
     */
    public function getValue($objectOrArray, $propertyPath, FormBuilder $formConfig)
    {
        if (!is_a($objectOrArray, 'EasyRdf_Resource')) {
            return $objectOrArray;
            throw new UnexpectedTypeException($objectOrArray, 'EasyRdf_Resource');
        }

        if (is_string($propertyPath)) {
            $propertyPath = new PropertyPath($propertyPath);
        } elseif (!$propertyPath instanceof PropertyPathInterface) {
            throw new UnexpectedTypeException($propertyPath, 'string or Symfony\Component\PropertyAccess\PropertyPathInterface');
        }

        $property = (string)$propertyPath;
        $resources = null;
        if ($formConfig->getOption('multiple')) {
            $resources = $objectOrArray->all($property);
        }
        else if($formConfig->getType()->getName() == 'collection') {
            $resources = $objectOrArray->all($property);
        }
        else {
            $resources = $objectOrArray->get($property);
        }
        return $this->getLiteralValues($resources);
    }

    /**
     * @param $objectOrArray
     * @param $propertyPath
     * @param $formConfig
     * @param $value
     * @return mixed
     */
    public function setValue(&$objectOrArray, $propertyPath, $formConfig, $value)
    {
        if (!is_a($objectOrArray, 'EasyRdf_Resource')) {
            throw new UnexpectedTypeException($objectOrArray, 'EasyRdf_Resource');
        }

        if (is_string($propertyPath)) {
            $propertyPath = new PropertyPath($propertyPath);
        } elseif (!$propertyPath instanceof PropertyPathInterface) {
            throw new UnexpectedTypeException($propertyPath, 'string or Symfony\Component\PropertyAccess\PropertyPathInterface');
        }

        //$objectOrArray = new \EasyRdf_Resource();
        $property = (string)$propertyPath;
        if(is_array($value) || $value instanceof \Traversable) {
            $itemsToAdd = is_object($value) ? iterator_to_array($value) : $value;
            $itemToRemove = array();
            $previousValue = $objectOrArray->all($property);

            if (is_array($previousValue) || $previousValue instanceof \Traversable) {
                foreach ($previousValue as $previousItem) {
                    foreach ($value as $key => $item) {
                        if ($item === $previousItem) {
                            // Item found, don't add
                            unset($itemsToAdd[$key]);
                            // Next $previousItem
                            continue 2;
                        }
                    }

                    // Item not found, add to remove list
                    $itemToRemove[] = $previousItem;
                }
            }

            foreach ($itemToRemove as $item) {
                //call_user_func(array($object, $methods[1]), $item);
                $objectOrArray->delete($property, $item);
            }
            foreach ($itemsToAdd as $item) {
                $objectOrArray->add($property, $item);
            }
            return;
        }

        return $objectOrArray->set($property, $value);
    }

    /**
     * @param $resources
     * @return array|string
     */
    public function getLiteralValues($resources)
    {
        if ($resources) {
            if (is_array($resources)) {
                $array = [];
                foreach ($resources as $resource) {
                    $array[] = $this->getLiteralValue($resource);
                }
                return $array;
            } else {
                return $this->getLiteralValue($resources);
            }
        }
    }

    /**
     * @param $resource
     * @return string
     */
    public function getLiteralValue($resource)
    {
        if ($resource instanceof \EasyRdf_Literal) {
            return $resource->getValue();
        }
    }
}
