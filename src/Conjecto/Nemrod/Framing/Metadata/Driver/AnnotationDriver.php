<?php
namespace Conjecto\Nemrod\Framing\Metadata\Driver;

use Conjecto\Nemrod\Framing\Metadata\ClassMetadata;
use Conjecto\Nemrod\Framing\Metadata\MethodMetadata;
use Doctrine\Common\Annotations\Reader;
use Metadata\Driver\DriverInterface;

/**
 * Extending AnnotationDriver to handle JsonLD options.
 */
class AnnotationDriver implements DriverInterface
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
        $this->reader = $reader;
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return ClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $classMetadata = new ClassMetadata($class->getName());
        $annotation = $this->reader->getClassAnnotation($class, 'Conjecto\\Nemrod\\Framing\\Annotation\\JsonLd');
        if (null !== $annotation) {
            // frame
            $classMetadata->setFrame($annotation->frame);
            // options
            $classMetadata->setOptions($annotation->options);
        }

        foreach ($class->getMethods() as $reflectionMethod) {
            $methodMetadata = new MethodMetadata($class->getName(), $reflectionMethod->getName());
            $annotation = $this->reader->getMethodAnnotation(
              $reflectionMethod,
              'Conjecto\\Nemrod\\Framing\\Annotation\\JsonLd'
            );
            if (null !== $annotation) {
                $methodMetadata->setFrame($annotation->frame);
                $methodMetadata->setOptions($annotation->options);
            }
            $classMetadata->addMethodMetadata($methodMetadata);
        }

        return $classMetadata;
    }
}
