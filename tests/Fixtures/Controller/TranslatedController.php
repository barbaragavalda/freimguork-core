<?php

namespace Core\Tests\Fixtures\Controller;

use Core\Controller\Controller;
use Core\Routing\Attribute\Route;

#[Route('/recepta')]
class TranslatedController extends Controller
{

    #[Route('/{uri}', name: 'translated.show')]
    public function show(): void
    {
    }

}
