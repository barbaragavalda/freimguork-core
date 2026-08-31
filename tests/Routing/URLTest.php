<?php

namespace Core\Tests\Routing;

use Core\Routing\URL;
use PHPUnit\Framework\TestCase;

class URLTest extends TestCase
{

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'], $_SERVER['HTTPS']);
    }

    public function testCombinesHostAndPathWhenRequestUriIsRelative(): void
    {
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = '/ca/receptes';

        $url = new URL();

        $this->assertSame('example.com/ca/receptes/', $url->getUserURL());
    }

    public function testStripsTheSchemeWhenRequestUriIsAlreadyAbsolute(): void
    {
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = 'https://example.com/ca/receptes';

        $url = new URL();

        $this->assertSame('example.com/ca/receptes/', $url->getUserURL());
    }

    public function testAlwaysEndsWithATrailingSlash(): void
    {
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = '/already/slashed/';

        $url = new URL();

        $this->assertSame('example.com/already/slashed/', $url->getUserURL());
    }

    public function testProtocolIsHttpByDefaultAndHttpsWhenSet(): void
    {
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = '/';

        $this->assertSame('http://', (new URL())->getProtocol());

        $_SERVER['HTTPS'] = 'on';

        $this->assertSame('https://', (new URL())->getProtocol());
    }

    public function testGetFullUserURLCombinesProtocolAndUserURL(): void
    {
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = '/ca';

        $url = new URL();

        $this->assertSame('http://example.com/ca/', $url->getFullUserURL());
    }

    public function testEmptyWhenHostAndUriAreMissing(): void
    {
        $url = new URL();

        $this->assertSame('/', $url->getUserURL());
    }

}
