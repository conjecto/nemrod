<?php
namespace Conjecto\RAL\Framing\Metadata;

trait MetadataTrait
{
    /**
     * JsonLD : frame.
     *
     * @var
     */
    public $frame;

    /**
     * JsonLD : options.
     *
     * @var
     */
    public $options = array();

    /**
     * @return mixed
     */
    public function getFrame()
    {
        return $this->frame;
    }

    /**
     * @param mixed $frame
     */
    public function setFrame($frame)
    {
        $this->frame = $frame;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }
}
