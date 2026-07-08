<?php

namespace Core\Routing;

/**
 * Class RouteCompiler
 * Turns a route path pattern ("/blog/{slug}") into a matching regex.
 */
class RouteCompiler
{

    private const string PARAM_PATTERN = '/^\{(\w+)(\?)?\}$/';

    /**
     * @return array{regex: string, paramNames: array<string>}
     */
    public static function compile(string $path, array $requirements = array()): array
    {
        $path       = '/' . trim($path, '/');
        $paramNames = array();
        $regexParts = array();

        foreach (explode('/', $path) as $segment) {
            if ($segment === '') {
                continue;
            }

            if (preg_match(self::PARAM_PATTERN, $segment, $matches) === 1) {
                $name       = $matches[1];
                $optional   = isset($matches[2]) && $matches[2] === '?';
                $pattern    = $requirements[ $name ] ?? '[^/]+';
                $group      = '(?P<' . $name . '>' . $pattern . ')';
                $paramNames[] = $name;

                $regexParts[] = $optional ? '(?:/' . $group . ')?' : '/' . $group;
            } else {
                $regexParts[] = '/' . preg_quote($segment, '#');
            }
        }

        $regex = '#^' . (count($regexParts) ? implode('', $regexParts) : '/') . '$#';

        return array('regex' => $regex, 'paramNames' => $paramNames);
    }

    /**
     * normalizes a URL path the same way route paths are normalized,
     * so compiled regexes and incoming request paths line up
     */
    public static function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }

}
