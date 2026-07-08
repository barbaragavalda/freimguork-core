<?php

namespace Core\Tests\Fixtures\Controller\SingleAction;

use Core\Controller\Controller;

/**
 * mirrors the shared-base-class pattern used by real apps: build() is
 * defined once here and never overridden, per-page behavior lives in run()
 */
abstract class BaseController extends Controller
{

    public function build(): void
    {
        $this->run();
    }

    abstract protected function run(): void;

}
