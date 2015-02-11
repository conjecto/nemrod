<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/02/2015
 * Time: 11:39
 */

namespace Devyn\Component\RAL\Manager\Event;

/**
 * Class Events defines all
 * @package Devyn\Component\RAL\Manager\Event
 */
final class Events
{
    /**
     * Event triggered before flush operation starts
     */
    const PreFlush = 'ral.pre_flush';

    /**
     * Event triggered after flush operation starts
     */
    const PostFlush = 'ral.post_flush';

    /**
     * Event triggered before resource removing operation
     */
    const PreRemove = 'ral.pre_remove';

    /**
     * Event triggered before resource creation
     */
    const PostRemove = 'ral.post_remove';

    /**
     * Event triggered after resource creation
     */
    const PostCreate = 'ral.post_create';

    /**
     * Event triggered before resource persist operation
     */
    const PrePersist = 'ral.pre_persist';

    /**
     * Event triggered after resource persist operation
     */
    const PostPersist = 'ral.post_persist';

    /**
     * Event triggered after resource persist operation
     */
    const OnClear = 'ral.on_clear';
} 