<?php
namespace Devyn\Component\RAL\Mapping\Driver;

use Devyn\Component\RAL\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;

/**
 * Class AnnotationDriver parses a bundle for
 * @package Devyn\Component\RAL\Mapping\Driver
 */
class AnnotationDriver extends AbstractAnnotationDriver
{
    /**
     * {@inheritDoc}
     */
    protected $entityAnnotationClasses = array(
      'Devyn\Component\RAL\Annotation\Resource' => 1
    );

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function loadMetadataForClass($className, \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata = null)
    {
        $metadata = new ClassMetadata();
        $class = new \ReflectionClass($className);
        $classAnnotations = $this->reader->getClassAnnotations($class);

        if ($classAnnotations) {
            foreach ($classAnnotations as $key => $annot) {
                if ( ! is_numeric($key)) {
                    continue;
                }
                $classAnnotations[get_class($annot)] = $annot;
            }
        }

        // Evaluate Resource annotation
        if (isset($classAnnotations['Devyn\Component\RAL\Annotation\Resource'])) {
            $resourceAnnot = $classAnnotations['Devyn\Component\RAL\Annotation\Resource'];
            $types = $resourceAnnot->types;
            if(!is_array($types)) {
                $types = array($types);
            }
            $metadata->types = $types;
        }

        // to do : more doctrine style ?
        return $metadata;
    }
}
