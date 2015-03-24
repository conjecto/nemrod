<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Mapping\Driver;

use Conjecto\Nemrod\ResourceManager\Mapping\ClassMetadata;
use Conjecto\Nemrod\ResourceManager\Mapping\PropertyMetadata;
use Doctrine\Common\Annotations\Reader;
use Metadata\Driver\AdvancedDriverInterface;

/**
 * Class AnnotationDriver parses a bundle for.
 */
class AnnotationDriver implements AdvancedDriverInterface
{
    /**
     * @var
     */
    private $dirs;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader, $dirs)
    {
        $this->reader = $reader;
        $this->dirs = $dirs;
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return \Metadata\ClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $metadata = new ClassMetadata($class->getName());

        // Resource annotation
        $annotation = $this->reader->getClassAnnotation($class, 'Conjecto\\Nemrod\\ResourceManager\\Annotation\\Resource');
        if (null !== $annotation) {
            $types = $annotation->types;
            $pattern = $annotation->uriPattern;
            if (!is_array($types)) {
                $types = array($types);
            }
            $metadata->types = $types;
            $metadata->uriPattern = $pattern;
        }

        foreach ($class->getProperties() as $reflectionProperty) {
            $propMetadata = new PropertyMetadata($class->getName(), $reflectionProperty->getName());

            // Property annotation
            $annotation = $this->reader->getPropertyAnnotation(
              $reflectionProperty,
              'Conjecto\\Nemrod\\ResourceManager\\Annotation\\Property'
            );
            if (null !== $annotation) {
                $propMetadata->value = $annotation->value;
                $propMetadata->cascade = $annotation->cascade;
            }

            $metadata->addPropertyMetadata($propMetadata);
        }

        return $metadata;
    }

    /**
     * @return array
     */
    public function getAllClassNames()
    {
        $classes = array();
        foreach ($this->dirs as $nsPrefix => $dir) {
            /** @var $iterator \RecursiveIteratorIterator|\SplFileInfo[] */
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (($fileName = $file->getBasename('.php')) == $file->getBasename()) {
                    continue;
                }
                $classes[] = $nsPrefix.'\\RdfResource\\'.str_replace('.', '\\', $fileName);
            }
        }

        return $classes;
    }
}
