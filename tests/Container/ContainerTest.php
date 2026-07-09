<?php

namespace Core\Tests\Container;

use Core\Container\Container;
use Core\Container\Exception\ContainerException;
use Core\Container\Exception\NotFoundException;
use Core\Tests\Fixtures\Container\Broken;
use Core\Tests\Fixtures\Container\Car;
use Core\Tests\Fixtures\Container\Engine;
use Core\Tests\Fixtures\Container\Greeter;
use Core\Tests\Fixtures\Container\OptionalEngine;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{

    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        Container::setCurrent(null);
    }

    protected function tearDown(): void
    {
        Container::setCurrent(null);
    }

    public function testAutowiresClassWithNoConstructor(): void
    {
        $this->assertInstanceOf(Engine::class, $this->container->get(Engine::class));
    }

    public function testAutowiresRequiredClassTypedDependency(): void
    {
        $car = $this->container->make(Car::class);

        $this->assertInstanceOf(Engine::class, $car->engine);
    }

    public function testUnboundClassesAreBuiltFreshEveryTime(): void
    {
        $a = $this->container->get(Engine::class);
        $b = $this->container->get(Engine::class);

        $this->assertNotSame($a, $b);
    }

    public function testExplicitInstanceBindingIsSharedAndUsedForAutowiring(): void
    {
        $engine = new Engine();
        $this->container->instance(Engine::class, $engine);

        $this->assertSame($engine, $this->container->get(Engine::class));

        $car = $this->container->make(Car::class);
        $this->assertSame($engine, $car->engine);
    }

    public function testSingletonFactoryIsMemoizedAfterFirstResolution(): void
    {
        $this->container->singleton(Engine::class, fn() => new Engine());

        $a = $this->container->get(Engine::class);
        $b = $this->container->get(Engine::class);

        $this->assertSame($a, $b);
    }

    public function testMakeAlwaysBuildsFreshEvenForAnExplicitlyBoundClass(): void
    {
        $engine = new Engine();
        $this->container->instance(Engine::class, $engine);

        $fresh = $this->container->make(Engine::class);

        $this->assertNotSame($engine, $fresh);
    }

    public function testScalarDefaultIsUsedWhenNoOverrideIsGiven(): void
    {
        $greeter = $this->container->make(Greeter::class);

        $this->assertSame('World', $greeter->name);
    }

    public function testExplicitParameterOverridesTheDefaultValue(): void
    {
        $greeter = $this->container->make(Greeter::class, array('name' => 'Barbara'));

        $this->assertSame('Barbara', $greeter->name);
    }

    public function testNullableTypedParameterIsStillAutowiredToARealInstance(): void
    {
        $optional = $this->container->make(OptionalEngine::class);

        $this->assertInstanceOf(Engine::class, $optional->engine);
    }

    public function testUnresolvableRequiredScalarParameterThrows(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->make(Broken::class);
    }

    public function testGetThrowsNotFoundForAnUnknownId(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('Core\\Tests\\Fixtures\\Container\\NoSuchClass');
    }

    public function testHasReflectsExplicitBindingsAndRealClassNames(): void
    {
        $this->assertFalse($this->container->has('Core\\Tests\\Fixtures\\Container\\NoSuchClass'));
        $this->assertTrue($this->container->has(Engine::class));

        $this->container->instance(Greeter::class, new Greeter('bound'));
        $this->assertTrue($this->container->has(Greeter::class));
    }

    public function testGetCurrentReflectsSetCurrent(): void
    {
        $this->assertNull(Container::getCurrent());

        Container::setCurrent($this->container);
        $this->assertSame($this->container, Container::getCurrent());

        Container::setCurrent(null);
        $this->assertNull(Container::getCurrent());
    }

}
