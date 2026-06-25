<?php

namespace Core\Routing;

/**
 * Class Router
 * Empty router because web is going to redirect to language URL
 */
class RedirectRouter extends Router
{

    public function getController(): string
    {
        return 'Core\\Controller\\RedirectLang';
    }

    public function getParams(): array
    {
        return array();
    }

    public function getParts(): array
    {
        return array();
    }

}