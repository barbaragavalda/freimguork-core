<?php

namespace Core\Tests\Fixtures\Controller;

use Core\Controller\Controller;
use Core\Routing\Attribute\Route;

#[Route('/blog')]
class BlogController extends Controller
{

    #[Route('/', name: 'blog.index')]
    public function index(): void
    {
    }

    #[Route('/{slug}', name: 'blog.show', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(): void
    {
    }

    #[Route('/{id}', name: 'blog.update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(): void
    {
    }

}
