<?php
namespace Devyn\Component\TypeMapper\Driver;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Class AnnotationMappingDriver parses a bundle for
 * @package Devyn\Component\TypeMapper\Driver
 */
class AnnotationMappingDriver
{

    private $includedFiles;

    /**
     *
     */
    public function __construct()
    {
        $this->includedFiles = array();
    }


    /**
     * Seeks all php class file in a given bundle, for a given base directory
     *
     * @param $bundleClass
     * @param $resourceDir
     */
    public function addResourcePath($resourcePath){



        if (is_dir($resourcePath)) {

            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($resourcePath, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+' . preg_quote('php') . '$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {

                $sourceFile = $file[0];

                if (!preg_match('(^phar:)i', $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                require_once $sourceFile;

                $this->includedFiles[] = $sourceFile;
            }
        }
    }

    /**
     * registers all resource classes to EasyRdf TypeMapper
     */
    public function registerMappings()
    {
        //get all declared classes
        $declared = get_declared_classes();

        $reader = new AnnotationReader();

        //if a class correspond to a previously included file, has proper annotation AND implements resource base class,
        //it is added to typemapper
        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $this->includedFiles) ) {
                $reflection = new \ReflectionClass($className);
                $RdfResourceAnnotation = $reader->getClassAnnotation($reflection,"Devyn\\Component\\TypeMapper\\Annotation\\RdfResource");
                //var_dump($RdfResourceAnnotation);//->getClassName();
                if (!empty($RdfResourceAnnotation)) {
                    $uris = $RdfResourceAnnotation->getUris();

                    if (!empty($uris)) {
                        foreach ($uris as $uri) {
                            \EasyRdf_TypeMapper::set($uri, $reflection->getName());
                        }
                    }
                }
            }
        }
    }
} 