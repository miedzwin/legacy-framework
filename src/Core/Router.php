<?php

namespace Core;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Router as BaseRouter;

class Router extends BaseRouter
{
    /**
     * @var YamlFileLoader
     */
    private $yamlFileLoader;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $fileLocator = new FileLocator(__DIR__ . '/../config');
        $this->yamlFileLoader = new YamlFileLoader($fileLocator);
        parent::__construct($this->yamlFileLoader, 'routes.yml');
    }
}
