<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 14/01/2015
 * Time: 17:03
 */

namespace Conjecto\EasyRdfBundle\Form\Type;


use Devyn\Component\Form\Extension\Core\Type\ResourceFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class PersonType extends ResourceFormType
{

    protected $rm;

    public function __construct($nsReg, $rm){
        $this->rm = $rm;
        return parent::__construct($nsReg);
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('foaf:givenName', 'text', array('label' => 'nom usuel'));
        $builder->add('foaf:name', 'text', array('label' => 'nom'));
        //$builder->add('ogbd:bestFriend', 'resource', array("class" => 'foaf:Person', 'label' => 'association'));
        $builder->add('vcard:hasAddress', 'resource_address',
            array('label' => 'Emplacement',
                "data_class" => 'Devyn\Component\RAL\Resource\Resource',
                "empty_data" => function (FormInterface $form) {
                    //@todo make it as a "subresource" method
                    //$res = $form->getRoot()->getData()->getGraph()->resource($this->rm->getUnitOfWork()->nextBNode(), array('vcard:hasAddress'));
                    $res = $this->rm->create('vcard:Address');
                    return $res;
                }
            )
        );
    }

    public function getParent()
    {
        return 'resource_form';
    }

    public function getName()
    {
        return 'resource_person';
    }
} 