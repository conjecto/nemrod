<?php
namespace Conjecto\RAL\Bundle\Serializer\Metadata;

use JMS\Serializer\Metadata\ClassMetadata as BaseClassMetadata;
use JMS\Serializer\Exception\InvalidArgumentException;
use Metadata\MergeableInterface;

/**
 * Extend Serializer ClassMetadata to handle extra options
 *
 * @package Conjecto\RAL\Bundle\Serializer\Metadata;
 */
class ClassMetadata extends BaseClassMetadata
{
    /**
     * JsonLD : frame
     * @var
     */
    public $jsonLdFrame;

    /**
     * JsonLD : compact
     * @var
     */
    public $jsonLdCompact;

    /**
     * @param MergeableInterface $object
     * @throws InvalidArgumentException
     */
    public function merge(MergeableInterface $object)
    {
        if ( ! $object instanceof ClassMetadata) {
            throw new InvalidArgumentException('$object must be an instance of ClassMetadata.');
        }
        parent::merge($object);

        $this->jsonLdFrame = $object->jsonLdFrame;
        $this->jsonLdCompact = $object->jsonLdCompact;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->jsonLdFrame,
            $this->jsonLdCompact,
            parent::serialize(),
          ));
    }

    /**
     * @param string $str
     */
    public function unserialize($str)
    {
        list(
          $this->jsonLdFrame,
          $this->jsonLdCompact,
          $parentStr
          ) = unserialize($str);
        parent::unserialize($parentStr);
    }

    /**
     * Special unserialize method used in the drivers
     * @param $parentStr
     */
    public function unserializeFromParent($parentStr)
    {
        parent::unserialize($parentStr);
    }
}
