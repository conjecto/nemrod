<?php
namespace Conjecto\RAL\ResourceManager\Mapping\Driver;

use Conjecto\RAL\ResourceManager\Mapping\ClassMetadata;
use Conjecto\RAL\ResourceManager\Mapping\PropertyMetadata;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Metadata\Driver\AdvancedDriverInterface;
use Conjecto\RAL\ResourceManager\Annotation\Property as RdfProperty;

/**
 * Class AnnotationDriver parses a bundle for
 * @package Conjecto\RAL\ResourceManager\Mapping\Driver
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
     * @return \Metadata\ClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $metadata = new ClassMetadata($class->getName());

        // Resource annotation
        $annotation = $this->reader->getClassAnnotation($class, 'Conjecto\\RAL\\ResourceManager\\Annotation\\Resource');
        if(null !== $annotation) {
            $types = $annotation->types;
            $pattern = $annotation->uriPattern;
            if(!is_array($types)) {
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
              'Conjecto\\RAL\\ResourceManager\\Annotation\\Property'
            );
            if(null !== $annotation) {
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
