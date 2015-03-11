<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 30/12/2014
 * Time: 10:53
 */

namespace Conjecto\RAL\Form\Extension\Core\Type;


use Conjecto\RAL\Form\Extension\Core\DataMapper\ResourceLabelAccessor;
use Conjecto\RAL\Form\Extension\Core\DataMapper\ResourcePropertyAccessor;
use Conjecto\RAL\Form\Extension\Core\DataMapper\ResourcePropertyPathMapper;
use Conjecto\RAL\ResourceManager\Manager\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ResourceType
 * @package Conjecto\RAL\Form\Extension\Core\Type
 */
class ResourceType extends AbstractType
{
    /**
     * @var Manager $rm
     */
    protected $rm;

    /**
     * @param Manager $defaultManager
     */
    function __construct(Manager $defaultManager)
    {
        $this->rm = $defaultManager;
    }

    /**
     * Add options type and property used to find resources in repository
     * @param $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choiceList = function (Options $options) {
            if (!$options['rm'] && $options['qb']) {
                throw new MissingOptionsException('You have to specify a resource manager or a query builder');
            }
            return new ResourceChoiceList(
                $options['rm'],
                $options['choices'],
                $options['class'],
                $options['query_builder'],
                $options['property'],
                $options['preferred_choices'],
                $options['group_by'],
                null,
                new ResourceLabelAccessor()
            );
        };

        $resolver->setDefaults(array(
            'choice_list' => $choiceList,
            'rm' => $this->rm,
            'query_builder' => null,
            'property' => 'rdfs:label',
            'group_by' => null
        ));

        $resolver->setRequired(array('class', 'property'));
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