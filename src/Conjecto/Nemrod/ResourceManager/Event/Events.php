<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/02/2015
 * Time: 11:39.
 */

namespace Conjecto\Nemrod\ResourceManager\Event;

/**
 * Class Events defines all.
 */
final class Events
{
    /**
     * Event triggered before flush operation starts.
     */
    const PreFlush = 'nemrod.pre_flush';

    /**
     * Event triggered after flush operation starts.
     */
    const PostFlush = 'nemrod.post_flush';

    /**
     * Event triggered before resource removing operation.
     */
    const PreRemove = 'nemrod.pre_remove';

    /**
     * Event triggered before resource creation.
     */
    const PostRemove = 'nemrod.post_remove';

    /**
     * Event triggered after resource creation.
     */
    const PostCreate = 'nemrod.post_create';

    /**
     * Event triggered before resource persist operation.
     */
    const PrePersist = 'nemrod.pre_persist';

    /**
     * Event triggered after resource persist operation.
     */
    const PostPersist = 'nemrod.post_persist';

    /**
     * Event triggered before resource persist operation.
     */
    const PreUpdate = 'nemrod.pre_update';

    /**
     * Event triggered after resource persist operation.
     */
    const PostUpdate = 'nemrod.post_update';

    /**
     * Event triggered after resource persist operation.
     */
    const OnClear = 'nemrod.on_clear';
}
