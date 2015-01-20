<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 30/12/2014
 * Time: 10:53
 */

namespace Devyn\Component\Form\Extension\Core\Type;


use Devyn\Component\RAL\Manager\Manager;
use Devyn\Component\RAL\Registry\TypeMapperRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ResourceType
 * @package Devyn\Component\Form\Extension\Core\Type
 */
class ResourceType extends AbstractType
{
    /**
     * @var Manager
     */
    protected $rm;

    /**
     * @var TypeMapperRegistry
     */
    protected $typeMapperRegistry;

    /**
     * @param Manager $rm
     */
    public function __construct(Manager $rm, TypeMapperRegistry $typeMapperRegistry)
    {
        $this->rm = $rm;
        $this->typeMapperRegistry = $typeMapperRegistry;
    }


    /**
     * Add options type and property used to find resources in repository
     * @param $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choiceList = function (Options $options) {
            return new ResourceChoiceList(
                $this->rm,
                $this->typeMapperRegistry,
                $options['choices'],
                $options['class']
            );
        };

        $resolver->setDefaults(array(
            'choice_list' => $choiceList,
        ));

        $resolver->setRequired(array('class'));
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'resource';
    }
} 