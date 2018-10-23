<?php

namespace Core\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as HttpKernelControllerResolver;

class ControllerResolver extends HttpKernelControllerResolver
{
    /**
     * @var null|ContainerInterface
     */
    private $container;

    /**
     * ControllerResolver constructor.
     * @param LoggerInterface|null $logger
     * @param ContainerInterface|null $container
     */
    public function __construct(LoggerInterface $logger = null, ContainerInterface $container = null)
    {
        $this->container = $container;
        parent::__construct($logger);
    }

    /**
     * @param string $class
     * @return object
     */
    protected function instantiateController($class)
    {
        try {
            return $this->container->get($class);
        } catch (\Exception $ex) {
            return new $class();
        }
    }
}