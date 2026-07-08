<?php

namespace Core\Tests\Fixtures\Controller\SingleAction;

use Core\Routing\Attribute\Route;

#[Route('/single-home', name: 'single.home')]
class HomeController extends BaseController
{

    protected function run(): void
    {
    }

}
