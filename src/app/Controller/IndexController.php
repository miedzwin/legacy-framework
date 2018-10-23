<?php

namespace App\Controller;

use App\Service\TestService;
use Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends Controller
{
    /**
     * @var TestService
     */
    private $testService;

    /**
     * IndexController constructor.
     * @param TestService $testService
     */
    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        var_dump($this->testService);
        die('hello world');
    }
}