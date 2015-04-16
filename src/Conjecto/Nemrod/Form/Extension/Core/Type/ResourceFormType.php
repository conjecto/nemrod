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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class ResourceFormType.
 */
class ResourceFormType extends FormType
{
    /**
     * @var RdfNamespaceRegistry
     */
    protected $nsRegistry;

    /**
     * @param RdfNamespaceRegistry      $nsRegistry
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(Manager $nsRegistry, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->nsRegistry = $nsRegistry;
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
        $resolver->setDefaults(array(
            'data_class' => 'Conjecto\Nemrod\Resource',
            'empty_data' => function (FormInterface $form) {
                return $this->rm->getRepository('rdfs:Resource')->create();
            },
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
