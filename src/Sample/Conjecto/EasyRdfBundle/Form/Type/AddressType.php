<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 15/01/2015
 * Time: 18:16
 */

namespace Conjecto\EasyRdfBundle\Form\Type;


use Devyn\Component\Form\Extension\Core\Type\ResourceFormType;
use Symfony\Component\Form\FormBuilderInterface;

class AddressType extends ResourceFormType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('vcard:locality', 'text', array('label' => 'Localité'));
        //$builder->add('foaf:name', 'text', array('label' => 'nom'));
        $builder->add('vcard:street-address', 'text', array('label' => 'Adresse'));
    }

    public function getName()
    {
        return 'resource_address';
    }

    public function getParent()
    {
        return 'resource_form';
    }
} 