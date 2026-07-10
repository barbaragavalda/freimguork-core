<?php

namespace Core\View\Extension;

use Core\Model\Utils\StringUtils;
use Core\Routing\Router;
use Core\Utils\Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Twig extends AbstractExtension
{

    /**
     * @param ?Router $router the current request's router, if one was
     *                        matched - null for flows that never reach
     *                        routing (e.g. RedirectLang), matching
     *                        Router::getCurrent()'s existing contract
     */
    public function __construct(private readonly ?Router $router, private readonly Config $config)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('formatPrice', [$this, 'formatPrice']),
            new TwigFilter('formatArray', [$this, 'formatArray']),
            new TwigFilter('forJS', [$this, 'forJS']),
            new TwigFilter('customTrans', [$this, 'customTrans']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('path', [$this, 'path']),
            new TwigFunction('url', [$this, 'url']),
        ];
    }

    /**
     * builds a relative URL from a route name (reverse routing)
     */
    public function path(string $name, array $params = array()): string
    {
        if ($this->router === null) {
            return '';
        }
        return $this->router->generate($name, $params);
    }

    /**
     * builds an absolute URL from a route name (reverse routing)
     */
    public function url(string $name, array $params = array()): string
    {
        return rtrim($this->config->getDomain(), '/') . $this->path($name, $params);
    }

    public function formatPrice($price, $decimals = 2, $thousandsSep = '.', $decPoint = ','): string
    {
        return StringUtils::formatPrice($price, $decimals, $thousandsSep, $decPoint);
    }

    public function formatArray($string, $array): string
    {
        if (count($array) > 0) {
            return vsprintf($string, $array);
        }
        return $string;
    }

    public function forJS($string): string
    {
        return str_replace('\n', '', trim($string));
    }

    public function customTrans($string, $lang): string
    {
        $class = '\Web\Model\Util\Lang';
        if (class_exists($class)) {
            $lang = new $class($lang);
            return $lang->get($string);
        }
        return $string;
    }

}