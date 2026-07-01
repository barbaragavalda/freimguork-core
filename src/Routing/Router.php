<?php

namespace Core\Routing;

/**
 * Class Router
 * Determines witch controller is going to be initialized depending on the routing.php file
 */
class Router
{

    private ?URL $url;

    private ?string $appFolder;

    public function __construct($appFolder = null)
    {
        $this->url       = new URL();
        $this->appFolder = $appFolder;
    }

    public function doRouting($routing): void
    {
        $this->url->setRouting($routing);
        $this->url->loadParams();
    }

    public function getController(): string
    {
        $controller = $this->url->getController();
        $namespace  = $this->appFolder . '\\Controller\\';
        if (class_exists($namespace . $controller)) {
            return $namespace . $controller;
        } else {
            return $namespace . 'DefaultController';
        }
    }

    public function getParams(): array
    {
        return $this->url->getParams();
    }

    public function getParts(): array
    {
        return $this->url->getParts();
    }

}