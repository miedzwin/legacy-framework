<?php

namespace Core\Traits;

use Psr\Container\ContainerInterface;
use Core\Container;

trait ContainerAwareTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }
}
