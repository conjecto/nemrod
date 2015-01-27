<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 27/01/2015
 * Time: 10:15
 */

namespace Devyn\Component\ParamConverter;


use Devyn\Component\RAL\Manager\Manager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceParamConverter implements ParamConverterInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $defaultResourceManager;

    /**
     * @var Manager
     */
    protected $rm;

    public function __construct(Container $container, $defaultResourceManager = 'rm')
    {
        $this->defaultResourceManager = $defaultResourceManager;
        $this->container = $container;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request $request The request
     * @param ConfigurationInterface $configuration Contains the name, class and options of the object
     *
     * @return boolean True if the object has been successfully set, else false
     */
    function apply(Request $request, ConfigurationInterface $configuration)
    {
        $name    = $configuration->getName();
        $class   = $configuration->getClass();
        $options = $this->getOptions($configuration);

        if (null === $request->attributes->get($name, false)) {
            $configuration->setIsOptional(true);
        }

        // find by identifier?
        if (false === $object = $this->find($class, $request, $options, $name)) {
            // find by criteria
            if (false === $object = $this->findOneBy($class, $request, $options)) {
                if ($configuration->isOptional()) {
                    $object = null;
                } else {
                    throw new \LogicException('Unable to guess how to get a Doctrine instance from the request information.');
                }
            }
        }

        if (null === $object && false === $configuration->isOptional()) {
            throw new NotFoundHttpException(sprintf('%s object not found.', $class));
        }

        $request->attributes->set($name, $object);

        return true;
    }

    protected function find($class, Request $request, $options, $name)
    {
        if ($options['mapping'] || $options['exclude']) {
            return false;
        }

        $uri = $this->getIdentifier($request, $options, $name);

        if (false === $uri || null === $uri) {
            return false;
        }

        if (isset($options['repository_method'])) {
            $method = $options['repository_method'];
        } else {
            $method = 'find';
        }

        return $this->rm->getRepository($class)->$method($uri);
    }

    protected function getIdentifier(Request $request, $options, $name)
    {
        $key = 'uri';

        if (isset($options[$key])) {
            if (!is_array($options[$key])) {
                $name = $options[$key];
            } elseif (is_array($options[$key])) {
                $uri = array();
                foreach ($options[$key] as $field) {
                    $uri[$field] = $request->attributes->get($field);
                }

                return $uri;
            }
        }

        if ($request->attributes->has($name)) {
            return $request->attributes->get($name);
        }

        if ($request->attributes->has($key)) {
            return $request->attributes->get($key);
        }

        foreach ($options['mapping'] as $attribute => $field) {
            if ($attribute == $key) {
                return $field;
            }
        }

        return false;
    }

    protected function findOneBy($class, Request $request, $options)
    {
        if (!$options['mapping']) {
            $keys = $request->attributes->keys();
            $options['mapping'] = $keys ? array_combine($keys, $keys) : array();
        }

        foreach ($options['exclude'] as $exclude) {
            unset($options['mapping'][$exclude]);
        }

        if (!$options['mapping']) {
            return false;
        }

        $criteria = array();

        foreach ($options['mapping'] as $attribute => $field) {
            $criteria[$attribute] = '"' . $request->attributes->get($field) . '"';
        }

        if ($options['strip_null']) {
            $criteria = array_filter($criteria, function ($value) { return !is_null($value); });
        }

        if (!$criteria) {
            return false;
        }

        if (isset($options['repository_method'])) {
            $method = $options['repository_method'];
        } else {
            $method = 'findBy';
        }

        return $this->rm->getRepository($class)->$method($criteria)->offsetGet(1);
    }

    protected function getOptions(ConfigurationInterface $configuration)
    {
        return array_replace(array(
            'resource_manager' => null,
            'exclude'        => array(),
            'mapping'        => array(),
            'strip_null'     => false,
        ), $configuration->getOptions());
    }

    private function getManager($name)
    {
        if (null === $name) {
            return $this->container->get($this->defaultResourceManager);
        }

        return $this->container->get($name);
    }

    /**
     * Checks if the object is supported.
     *
     * @param ConfigurationInterface $configuration Should be an instance of ParamConverter
     *
     * @return boolean True if the object is supported, else false
     */
    function supports(ConfigurationInterface $configuration)
    {
        if (!$configuration instanceof ParamConverter) {
            return false;
        }

        // if there is no manager, this means that only Doctrine DBAL is configured
        if (null === $this->container/* null === $this->registry || !count($this->registry->getManagers())*/) {
            return false;
        }

        if (null === $configuration->getClass()) {
            return false;
        }

        // test an existing PHP class
        if (class_exists($configuration->getClass())) {
            return false;
        }

        $options = $this->getOptions($configuration);

        $rm = $this->getManager($options['resource_manager']);

        if (null === $rm) {
            return false;
        }

        $this->rm = $rm;

        return true;
    }
}