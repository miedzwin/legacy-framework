<?php

namespace App\Controller;

use App\Service\TestService;
use Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends Controller
{

    public function index(Request $request, TestService $testService)
    {
        var_dump($testService);
        die('hello world');
    }
}