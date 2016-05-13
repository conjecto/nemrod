<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Form\Extension\Core\Type;

use Conjecto\Nemrod\Form\Extension\Core\DataMapper\ResourcePropertyPathMapper;
use Conjecto\Nemrod\Manager;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class ResourceFormType.
 */
class ResourceFormType extends FormType
{
    /**
     * @var Manager
     */
    protected $rm;

    /**
     * @param Manager      $rm
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(Manager $rm, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->rm = $rm;
        parent::__construct($propertyAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // custom path mapper
        $builder
          ->setDataMapper($options['compound'] ? new ResourcePropertyPathMapper() : null);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // Derive "data_class" option from passed "data" object
        $dataClass = function (Options $options) {
            return isset($options['data']) && is_object($options['data']) ? get_class($options['data']) : 'Conjecto\Nemrod\Resource';
        };

        // Derive "empty_data" option from passed "data_class"
        $emptyData = function (Options $options) {
            $class = $options['data_class'];

            if (null !== $class) {
                $metadata = $this->rm->getMetadataFactory()->getMetadataForClass($class);
                $types = $metadata->getTypes();
                return function (FormInterface $form) use ($types) {
                    if(!$form->isEmpty() || $form->isRequired()) {
                        $class = count($types) ? $types[0] : 'rdfs:Resource'; // todo : rdfs:Resource ?
                        return $this->rm->getRepository($class)->create();

                    }
                    return null;
                };
            }

            return function (FormInterface $form) {
                return $form->getConfig()->getCompound() ? array() : '';
            };
        };

        // derive "error_mapping" option from passed "reference" object
        $errorMapping = function (Options $options) {
            $class = $options['data_class'];
            $mapping = array();

            if (null !== $class) {
                $metadata = $this->rm->getMetadataFactory()->getMetadataForClass($class);
                foreach ($metadata->propertyMetadata as $key => $propertyMetadata) {
                    if($propertyMetadata->value) {
                        $mapping[$key] = $propertyMetadata->value;
                    }
                }
            }

            return $mapping;
        };

        // set defaults
        $resolver->setDefaults(array(
            'data_class' => $dataClass,
            'empty_data' => $emptyData,
            'error_mapping' => $errorMapping
        ));
    }

    /**
     *
     */
    public function getParent()
    {
        return 'form';
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'resource_form';
    }
}
