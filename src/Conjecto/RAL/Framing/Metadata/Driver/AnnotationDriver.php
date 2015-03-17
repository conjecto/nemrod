<?php
namespace Conjecto\RAL\Framing\Metadata\Driver;

use Conjecto\RAL\Bundle\Serializer\Annotation\JsonLd;
use Conjecto\RAL\Bundle\Serializer\Metadata\ClassMetadata;
use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\Metadata\Driver\AnnotationDriver as BaseAnnotationDriver;

/**
 * Extending AnnotationDriver to handle JsonLD options
 *
 * @package Conjecto\RAL\Bundle\Serializer\Metadata\Driver;
 */
class AnnotationDriver extends BaseAnnotationDriver
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        $this->reader = $reader;
    }

    /**
     * @param \ReflectionClass $class
     * @return ClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        // get the original metadata from the parent class
        $parent = parent::loadMetadataForClass($class);

        // create the new instance and unserialize from original
        $classMetadata = new ClassMetadata($parent->name);
        $classMetadata->unserializeFromParent($parent->serialize());

        // process the new annotations
        foreach ($this->reader->getClassAnnotations($class) as $annot) {
            if ($annot instanceof JsonLd) {
                $classMetadata->jsonLdFrame = $annot->frame;
                $classMetadata->jsonLdCompact = $annot->compact;
            }
        }

        // return
        return $classMetadata;
    }
}
