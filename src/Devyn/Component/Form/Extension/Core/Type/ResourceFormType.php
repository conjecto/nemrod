<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Devyn\Component\Form\Extension\Core\Type;

use Devyn\Component\Form\Extension\Core\DataMapper\ResourcePropertyPathMapper;
use Devyn\Component\RdfNamespace\RdfNamespaceRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Extension\Core\EventListener\TrimListener;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class ResourceFormType
 * @package Symfony\Component\Form\Extension\Core\Type
 */
class ResourceFormType extends FormType
{
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

    public function findNameInForm($all)
    {
        $first = true;
        $firstName = "";

        foreach ($all as $one) {
            if ($one->getName() == 'rdfslabel' || $one->getName() == 'foafname') {
                $parentResourceName = $one->getParent()->getParent()->getName();
                $parentResourceName = str_replace('foaf', '', $parentResourceName);
                return $parentResourceName . '-' . $one->getViewData();
            }
            else if ($first) {
                $first = false;
                $firstName = $one->getName();
            }
        }

        return $firstName;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'EasyRdf_Resource',
            'empty_data' => function (FormInterface $form) {
                $parseUri = $form->getRoot()->getData()->parseUri();
                $newUri = $parseUri->getScheme() . '://' . $parseUri->getAuthority() . '/#';

                $all = $form->all();
                $resourceName = $this->findNameInForm($all);

                return new \EasyRdf_Resource($newUri . $resourceName, new \EasyRdf_Graph());
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
