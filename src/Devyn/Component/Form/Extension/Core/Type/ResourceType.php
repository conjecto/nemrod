<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 30/12/2014
 * Time: 10:53
 */

namespace Devyn\Component\Form\Extension\Core\Type;


use Devyn\Component\Form\Extension\Core\Type\ResourceChoiceList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ResourceType
 * @package Devyn\Component\Form\Extension\Core\Type
 */
class ResourceType extends AbstractType
{    /**
     * @param $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choiceList = function (Options $options) {
            return new ResourceChoiceList(
                $options['choices']
            );
        };

        $resolver->setDefaults(array(
            'choice_list' => $choiceList,
        ));
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