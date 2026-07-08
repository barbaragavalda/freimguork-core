<?php

namespace Core\Routing;

/**
 * Class Route
 * A single compiled route: which controller action answers which path/method.
 */
final class Route
{

    /**
     * @param array<string> $methods
     * @param array<string> $paramNames
     * @param array<string, string> $requirements
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $regex,
        public readonly array $paramNames,
        public readonly array $methods,
        public readonly string $controllerClass,
        public readonly string $action,
        public readonly array $requirements = array()
    ) {
    }

    public static function compile(
        string $path,
        array $methods,
        string $controllerClass,
        string $action,
        ?string $name = null,
        array $requirements = array()
    ): self {
        $path     = RouteCompiler::normalizePath($path);
        $compiled = RouteCompiler::compile($path, $requirements);

        return new self(
            $name ?? ($controllerClass . '::' . $action),
            $path,
            $compiled['regex'],
            $compiled['paramNames'],
            array_map('strtoupper', $methods),
            $controllerClass,
            $action,
            $requirements
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array(
            'name'            => $this->name,
            'path'            => $this->path,
            'regex'           => $this->regex,
            'paramNames'      => $this->paramNames,
            'methods'         => $this->methods,
            'controllerClass' => $this->controllerClass,
            'action'          => $this->action,
            'requirements'    => $this->requirements
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['path'],
            $data['regex'],
            $data['paramNames'],
            $data['methods'],
            $data['controllerClass'],
            $data['action'],
            $data['requirements']
        );
    }

}
