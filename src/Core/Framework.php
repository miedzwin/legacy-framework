<?php

namespace Core;

use Core\Exception\APIException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
    public function __construct($container, string $environment = '')
    {
        $this->container = $container;

        $this->matcher = $this->container->get('url_matcher');
        $this->controllerResolver = $this->container->get('controller_resolver');
        $this->argumentResolver = $this->container->get('argument_resolver');
        $this->environment = $environment;

        $this->initApp();
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
            $controller[0]->setContainer($this->container);
            $arguments = $this->argumentResolver->getArguments($request, $controller);

            return call_user_func_array($controller, $arguments);
        } catch (ResourceNotFoundException $ex) {
            return new Response('Not Found.', Response::HTTP_NOT_FOUND);
        } catch (APIException $ex) {
            return new JsonResponse([
                'success' => false,
                'errorHtmlCode' => $ex->getCode(),
                'errorMessage' => $ex->getMessage(),
            ]);
        } catch (\Exception $ex) {
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
