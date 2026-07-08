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

    /**
     * splits a URL path into its literal segments, in order, e.g.
     * "/shop/pro/2/" => ["shop", "pro", "2"]
     *
     * @return array<string>
     */
    public static function splitPath(string $path): array
    {
        $path = self::normalizePath($path);
        return array_values(array_filter(explode('/', $path), static fn ($part) => $part !== ''));
    }

    /**
     * strips a leading path prefix (e.g. a sub-project's resolved domain
     * path, such as a language segment) from a request path, so it lines up
     * with routes defined without that prefix. A no-op if the path doesn't
     * actually start with the prefix, or the prefix is just "/".
     */
    public static function stripPrefix(string $path, string $prefix): string
    {
        $path   = self::normalizePath($path);
        $prefix = self::normalizePath($prefix);

        if ($prefix !== '/' && str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }

        return self::normalizePath($path);
    }

}
