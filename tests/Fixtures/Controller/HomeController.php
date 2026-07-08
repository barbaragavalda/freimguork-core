<?php

namespace Core\Tests\Fixtures\Controller;

use Core\Controller\Controller;
use Core\Routing\Attribute\Route;

class HomeController extends Controller
{

    #[Route('/', name: 'home')]
    public function index(): void
    {
    }

}
