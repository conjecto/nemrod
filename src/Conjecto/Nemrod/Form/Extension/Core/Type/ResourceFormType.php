<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Form\Extension\Core\Type;

use Conjecto\Nemrod\Form\Extension\Core\DataMapper\ResourcePropertyPathMapper;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
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
     * @var RdfNamespaceRegistry
     */
    protected $nsRegistry;

    /**
     * @param RdfNamespaceRegistry      $nsRegistry
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(RdfNamespaceRegistry $nsRegistry, PropertyAccessorInterface $propertyAccessor = null)
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
     * Guess the suffix URI for a new Resource with type name and type value.
     *
     * @param $all
     *
     * @return string
     */
    public function findNameInForm($all)
    {
        $first = true;
        $firstName = "";

        foreach ($all as $one) {
            if ($one->getName() == 'rdfs:label' || $one->getName() == 'foaf:name') {
                $parentResourceName = $one->getParent()->getParent()->getName();
                foreach ($this->nsRegistry->namespaces() as $key => $namespace) {
                    if (strcmp($parentResourceName, $key) > 1) {
                        $parentResourceName = str_replace($key, '', $parentResourceName);
                        break;
                    }
                }

                return $parentResourceName.'-'.$one->getViewData();
            } elseif ($first) {
                $first = false;
                $firstName = $one->getName().'-'.$one->getViewData();
            }
        }

        return $firstName;
    }

    /**
     * Set default_options
     * Set data_class to EasyRdf\Resource by default
     * If a new item is added to a collection, a new resource is created.
     *
     * @todo change URI guessing
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => '\EasyRdf\Resource',
            'empty_data' => function (FormInterface $form) {
                $parseUri = $form->getRoot()->getData()->parseUri();
                $newUri = $parseUri->getScheme().'://'.$parseUri->getAuthority().'/#'.$this->findNameInForm($form->all());

                return new \EasyRdf\Resource($newUri, new \EasyRdf\Graph());
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
