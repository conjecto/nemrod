<?php

namespace Devyn\Bundle\RdfFrameworkBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Controller annotation to configure rdf view mode
 * @see Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface
 *
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
     * @var boolean
     */
    protected $compact = true;

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
     * @return boolean
     */
    public function isCompact()
    {
        return $this->compact;
    }

    /**
     * @param boolean $compact
     */
    public function setCompact($compact)
    {
        $this->compact = $compact;
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
