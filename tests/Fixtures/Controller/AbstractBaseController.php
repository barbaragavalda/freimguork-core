<?php

namespace Core\Tests\Fixtures\Controller;

use Core\Controller\Controller;
use Core\Routing\Attribute\Route;

/**
 * abstract fixture: must be skipped by AttributeRouteLoader even though it
 * carries a #[Route] attribute
 */
#[Route('/base')]
abstract class AbstractBaseController extends Controller
{

    #[Route('/', name: 'base.index')]
    public function index(): void
    {
    }

}
