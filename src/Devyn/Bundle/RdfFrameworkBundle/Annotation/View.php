<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Devyn\Bundle\RdfFrameworkBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * View annotation class.
 * @Annotation
 * @Target({"METHOD","CLASS"})
 */
class View extends Template
{
    /**
     * @var string
     */
    protected $format;

    /**
     * @var string
     */
    protected $frame;

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getFrame()
    {
        return $this->frame;
    }

    /**
     * @param string $frame
     */
    public function setFrame($frame)
    {
        $this->frame = $frame;
    }

    /**
     * Returns the annotation alias name.
     *
     * @return string
     * @see Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface
     */
    public function getAliasName()
    {
        return 'rdf_view';
    }
}
