<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/01/2015
 * Time: 16:04.
 */

namespace Conjecto\EasyRdfBundle\Form\Type;

use Conjecto\RAL\ResourceManager\Manager\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class RdfFormType.
 */
class RdfFormType extends AbstractType
{
    protected $rm;

    public function __construct(Manager $rm)
    {
        $this->rm = $rm;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('foaf:givenName', 'text', array('label' => 'nom usuel'));
        $builder->add('foaf:name', 'text', array('label' => 'nom'));
        $builder->add('foaf:knows', 'resource', [
            'label' => 'Connait',
            'expanded' => true,
            'multiple' => true,
            'class' => 'Conjecto\EasyRdfBundle\RdfResource\Person',
        ]);

        $builder->add('foaf:knows', 'collection', [
            'type' => new PersonType(),
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
        ]);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'cascade_validation' => true,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'rdf_form';
    }

    public function getParent()
    {
        return 'resource_form';
    }
}
