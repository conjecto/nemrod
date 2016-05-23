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

use Conjecto\Nemrod\Form\Extension\Core\DataMapper\ResourceLabelAccessor;
use Conjecto\Nemrod\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ResourceType.
 */
class ResourceType extends AbstractType
{
    /**
     * @var Manager
     */
    protected $rm;

    /**
     * @param Manager $defaultManager
     */
    public function __construct(Manager $defaultManager)
    {
        $this->rm = $defaultManager;
    }

    /**
     * Add options type and property used to find resources in repository.
     *
     * @param $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $preferred_resources = array();

        $choiceList = function (Options $options) {
            if (!$options['rm'] && $options['query_builder']) {
                throw new MissingOptionsException('You have to specify a resource manager or a query builder');
            }


            return new ResourceChoiceList(
                $options['rm'],
                $options['choices'],
                $options['class'],
                $options['query_builder'],
                $options['property']
            );
        };

        $resolver->setDefaults(array(
            'choice_list' => $choiceList,
            'rm' => $this->rm,
            'query_builder' => null,
            'property' => 'rdfs:label',
            'group_by' => null,
            'preferred_choices' => null
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
