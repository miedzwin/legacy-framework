<?php

namespace Core\Controller;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as HttpKernelControllerResolver;

class ControllerResolver extends HttpKernelControllerResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ControllerResolver constructor.
     * @param ContainerInterface $container
     * @param LoggerInterface|null $logger
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger = null)
    {
        $this->container = $container;
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function instantiateController($class)
    {
        return $this->configureController(parent::instantiateController($class));
    }

    /**
     * @param $controller
     * @return mixed
     */
    private function configureController($controller)
    {
        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->container);
        }
        if ($controller instanceof AbstractController && null !== $previousContainer = $controller->setContainer($this->container)) {
            $controller->setContainer($previousContainer);
        }

        return $controller;
    }
}