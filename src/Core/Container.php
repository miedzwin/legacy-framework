<?php

namespace Core;

use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container as DIContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Container extends DIContainer
{
    private $containerBuilder;

    /**
     * Container constructor.
     * @param ParameterBagInterface|null $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag = null)
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->registerComponents();
        parent::__construct($parameterBag);
    }

    private function registerComponents()
    {
        $this->containerBuilder->setParameter('routes', (new Router())->getRouteCollection());

        $this->loadServices();
    }

    /**
     * Loading services with configuration from file
     */
    private function loadServices(): void
    {
        $yamlLoader = new YamlFileLoader($this->containerBuilder, new FileLocator(__DIR__ . '/../config'));
        try {
            $yamlLoader->load('config.yml');
        } catch (\Exception $e) {
            echo $e->getMessage();
            die('An error occurred while registering services.');
        }

        $this->containerBuilder->compile();
    }

    /**
     * @return ContainerBuilder
     */
    public function getContainerBuilder(): ContainerBuilder
    {
        return $this->containerBuilder;
    }
}