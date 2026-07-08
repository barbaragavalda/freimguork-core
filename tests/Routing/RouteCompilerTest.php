<?php

namespace Core\Tests\Routing;

use Core\Routing\RouteCompiler;
use PHPUnit\Framework\TestCase;

class RouteCompilerTest extends TestCase
{

    public function testStaticPath(): void
    {
        $compiled = RouteCompiler::compile('/blog');

        $this->assertSame(array(), $compiled['paramNames']);
        $this->assertSame(1, preg_match($compiled['regex'], '/blog'));
        $this->assertSame(0, preg_match($compiled['regex'], '/blog/extra'));
    }

    public function testRootPath(): void
    {
        $compiled = RouteCompiler::compile('/');

        $this->assertSame(1, preg_match($compiled['regex'], '/'));
        $this->assertSame(0, preg_match($compiled['regex'], '/anything'));
    }

    public function testRequiredParam(): void
    {
        $compiled = RouteCompiler::compile('/blog/{slug}');

        $this->assertSame(array('slug'), $compiled['paramNames']);
        $this->assertSame(1, preg_match($compiled['regex'], '/blog/hello-world', $matches));
        $this->assertSame('hello-world', $matches['slug']);
        $this->assertSame(0, preg_match($compiled['regex'], '/blog/'));
    }

    public function testRequirementOverridesDefaultPattern(): void
    {
        $compiled = RouteCompiler::compile('/blog/{id}', array('id' => '\d+'));

        $this->assertSame(1, preg_match($compiled['regex'], '/blog/42'));
        $this->assertSame(0, preg_match($compiled['regex'], '/blog/abc'));
    }

    public function testOptionalTrailingParam(): void
    {
        $compiled = RouteCompiler::compile('/blog/{page?}');

        $this->assertSame(1, preg_match($compiled['regex'], '/blog'));
        $this->assertSame(1, preg_match($compiled['regex'], '/blog/2', $matches));
        $this->assertSame('2', $matches['page']);
    }

    public function testNormalizePathTrimsTrailingSlash(): void
    {
        $this->assertSame('/blog/hello', RouteCompiler::normalizePath('/blog/hello/'));
        $this->assertSame('/', RouteCompiler::normalizePath('/'));
        $this->assertSame('/', RouteCompiler::normalizePath(''));
    }

    public function testSplitPathReturnsOrderedSegments(): void
    {
        $this->assertSame(['shop', 'pro', '2'], RouteCompiler::splitPath('/shop/pro/2/'));
        $this->assertSame(['shop', 'pro', '2'], RouteCompiler::splitPath('shop/pro/2'));
    }

    public function testSplitPathOfRootIsEmpty(): void
    {
        $this->assertSame([], RouteCompiler::splitPath('/'));
        $this->assertSame([], RouteCompiler::splitPath(''));
    }

    public function testStripPrefixRemovesLeadingSegment(): void
    {
        $this->assertSame('/receptes', RouteCompiler::stripPrefix('/ca/receptes', '/ca'));
        $this->assertSame('/receptes/2024', RouteCompiler::stripPrefix('/ca/receptes/2024', '/ca/'));
    }

    public function testStripPrefixIsNoOpWhenPathDoesNotStartWithIt(): void
    {
        $this->assertSame('/receptes', RouteCompiler::stripPrefix('/receptes', '/es'));
    }

    public function testStripPrefixIsNoOpForRootPrefix(): void
    {
        $this->assertSame('/receptes', RouteCompiler::stripPrefix('/receptes', '/'));
        $this->assertSame('/receptes', RouteCompiler::stripPrefix('/receptes', ''));
    }

    public function testStripPrefixOfExactMatchLeavesRoot(): void
    {
        $this->assertSame('/', RouteCompiler::stripPrefix('/ca', '/ca'));
    }

}
