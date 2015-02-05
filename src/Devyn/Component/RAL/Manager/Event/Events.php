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
     * Event triggered before flush operation starts
     */
    const PostFlush = 'ral.post_flush';
} 