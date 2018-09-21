<?php

namespace Core;

use Core\Controller\ControllerResolver;
use Core\Exception\APIException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class Framework extends HttpKernel
{
    /**
     * @var UrlMatcherInterface
     */
    protected $matcher;

    /**
     * @var ControllerResolverInterface
     */
    protected $controllerResolver;

    /**
     * @var ArgumentResolverInterface
     */
    protected $argumentResolver;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Container
     */
    private $container = null;

    /**
     * Framework constructor.
     * @param string $environment
     * @throws \Exception
     */
    public function __construct(string $environment)
    {
        $this->buildContainer($environment);

        $this->matcher = $this->container->get(UrlMatcher::class);
        $this->controllerResolver = $this->container->get(ControllerResolver::class);
        $this->argumentResolver = $this->container->get(ArgumentResolver::class);
        $this->environment = $environment;

        $dispatcher = new EventDispatcher();
        $requestStack = new RequestStack();
        $dispatcher->addSubscriber(new RouterListener($this->matcher, $requestStack));
        $dispatcher->addSubscriber(new ResponseListener('UTF-8'));

        $this->initApp();

        parent::__construct($dispatcher, $this->controllerResolver, $requestStack, $this->argumentResolver);
    }

    /**
     * Handles a Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param Request $request A Request instance
     * @param int $type The type of the request
     *                         (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param bool $catch Whether to catch exceptions or not
     *
     * @return Response A Response instance
     *
     * @throws \Exception When an Exception occurs during processing
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $this->matcher->getContext()->fromRequest($request);

        try {
            $request->attributes->add($this->matcher->match($request->getPathInfo()));
            $controller = $this->controllerResolver->getController($request);

            $event = new FilterControllerEvent($this, $controller, $request, $type);
            $this->dispatcher->dispatch(KernelEvents::CONTROLLER, $event);
            $controller = $event->getController();

            // controller arguments
            $arguments = $this->argumentResolver->getArguments($request, $controller);

            $event = new FilterControllerArgumentsEvent($this, $controller, $arguments, $request, $type);
            $this->dispatcher->dispatch(KernelEvents::CONTROLLER_ARGUMENTS, $event);

            return call_user_func_array($event->getController(), $event->getArguments());
        } catch (ResourceNotFoundException $ex) {
            return new Response('Not Found.', Response::HTTP_NOT_FOUND);
        } catch (APIException $ex) {
            return new JsonResponse([
                'success' => false,
                'errorHtmlCode' => $ex->getCode(),
                'errorMessage' => $ex->getMessage(),
            ]);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            return new Response('An error occured.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws \Exception
     */
    private function initApp(): void
    {
        error_reporting(E_ALL ^ E_STRICT ^ E_NOTICE);
        date_default_timezone_set('UTC');

        $protocol = $this->checkProtocol(true);
        define('PROTOCOL', $protocol . '://');
    }

    /**
     * Initialize DI container
     */
    private function buildContainer(string $environment): void
    {
        $container = new Container();
        $container->setParameter('routes', (new Router())->getRouteCollection());
        $this->container = $container;

        $this->loadParameters();
        $this->loadServices();
        $this->container->compile();
        $this->initEnvironmentVars();
    }

    /**
     * Load parameters from file
     */
    private function loadParameters(): void
    {

    }

    /**
     * Loading services with configuration from file
     */
    private function loadServices(): void
    {
        $yamlLoader = new YamlFileLoader($this->container, new FileLocator(__DIR__ . '/../config'));
        try {
            $yamlLoader->load('config.yml');
        } catch (\Exception $e) {
            echo $e->getMessage();
            die('An error occurred while registering services.');
        }
    }

    /**
     * Get root directory of the project
     *
     * @return string
     */
    public function getRootDir(): string
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = dirname($r->getFileName()) . '/../..';
        }

        return $this->rootDir;
    }

    /**
     * Load environment variables from file and init them
     *
     * @throws \Exception
     */
    private function initEnvironmentVars()
    {

    }

    /**
     * Get string with app name
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Protocol checking (got from Facebook PHP SDK 3.2.3)
     *
     * @param $trustForwarded
     * @return string
     */
    private function checkProtocol($trustForwarded)
    {
        if ($trustForwarded && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            if ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                return 'https';
            }
            return 'http';
        }

        /* apache + variants specific way of checking for https */
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
            return 'https';
        }

        /* nginx way of checking for https */
        if (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] === '443')) {
            return 'https';
        }
        return 'http';
    }
}
