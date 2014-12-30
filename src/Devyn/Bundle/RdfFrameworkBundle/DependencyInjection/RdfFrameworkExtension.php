<?php

namespace Devyn\Bundle\RdfFrameworkBundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Validator\Validation;

/**
 * FrameworkExtension.
 */
class RdfFrameworkExtension extends Extension
{
    /**
     * Responds to the app.config configuration parameter.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     * @throws LogicException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('form.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if(isset($config['namespaces'])) {
            $this->registerRdfNamespaces($config['namespaces'], $container);
        }

        $this->loadResourceMapping($config, $container);
    }

    /**
     * Load the namespaces in registry
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function registerRdfNamespaces(array $config, ContainerBuilder $container)
    {
        $registry = $container->getDefinition('rdf_namespace_registry');
        foreach($config as $prefix => $data) {
            $registry->addMethodCall('set', array($prefix, $data['uri']));
        }
    }


    /**
     * Parses active bundles for resources to map
     *
     * @param ContainerBuilder $container
     */
    private function loadResourceMapping(array $config, ContainerBuilder $container){
        $resourceDir = $config['default_resource_directory'] ;
        $includedFiles = array();
        foreach ($container->getParameter('kernel.bundles') as $bundle=>$class) {

            $refl = new \ReflectionClass($class);;
            $path = pathinfo($refl->getFileName());

            $resourcePath = $path['dirname'] . "\\" . $resourceDir . "\\";

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

                    $includedFiles[] = $sourceFile;
                }
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles) ) {
                $classes[] = $className;
            }
        }

        //$this->classNames = $classes;

        $reader = new AnnotationReader();
        foreach ($classes as $classR) {
            $RdfResourceAnnotation = $reader->getClassAnnotation(new \ReflectionClass($classR),"Devyn\\Component\\TypeMapper\\Annotation\\RdfResource");
            var_dump($RdfResourceAnnotation);//->getClassName();
        }


        var_dump($classes);
    }


    public function getAlias()
    {
        return 'rdf_framework';
    }
}
