<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $jsonLdPath = $this->reader->getClassAnnotation($class, 'Conjecto\\Nemrod\\Framing\\Annotation\\JsonLd');
        if (null !== $jsonLdPath) {
            // frame
            $classMetadata->setFrame($jsonLdPath->frame);
            // options
            $classMetadata->setOptions($jsonLdPath->options);
        }
        $subClassOfProperty = $this->reader->getClassAnnotation($class, 'Conjecto\\Nemrod\\Framing\\Annotation\\SubClassOf');
        if (null !== $subClassOfProperty) {
            // ParentClass
            $classMetadata->setParentClass($subClassOfProperty->parentClass);
        }
        $typeProperty = $this->reader->getClassAnnotation($class, 'Conjecto\\Nemrod\\ResourceManager\\Annotation\\Resource');
        if (null !== $typeProperty) {
            // rdf:type
            $classMetadata->setTypes($typeProperty->types);
        }

        foreach ($class->getMethods() as $reflectionMethod) {
            $methodMetadata = new MethodMetadata($class->getName(), $reflectionMethod->getName());
            $jsonLdPath = $this->reader->getMethodAnnotation(
                $reflectionMethod,
                'Conjecto\\Nemrod\\Framing\\Annotation\\JsonLd'
            );
            if (null !== $jsonLdPath) {
                $methodMetadata->setFrame($jsonLdPath->frame);
                $methodMetadata->setOptions($jsonLdPath->options);
            }
            $subClassOfProperty = $this->reader->getMethodAnnotation(
                $reflectionMethod,
                'Conjecto\\Nemrod\\Framing\\Annotation\\SubClassOf'
            );
            if (null !== $subClassOfProperty) {
                $methodMetadata->setParentClass($subClassOfProperty->parentClass);
            }
            $typeProperty = $this->reader->getMethodAnnotation(
                $reflectionMethod,
                'Conjecto\\Nemrod\\ResourceManager\\Annotation\\Resource'
            );
            if (null !== $typeProperty) {
                $methodMetadata->setTypes($typeProperty->types);
            }
            $classMetadata->addMethodMetadata($methodMetadata);
        }

        return $classMetadata;
    }
}
