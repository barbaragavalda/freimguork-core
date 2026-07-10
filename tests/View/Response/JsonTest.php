<?php

namespace Core\Tests\View\Response;

use Core\Utils\Config;
use Core\View\Response\Json;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Json::__construct() unconditionally calls Config::getInstance(), which
 * would normally read config/projects(.dev).php from DIR_ROOT - files that
 * don't exist in this library repo (it's never run standalone, see
 * CLAUDE.md). Rather than changing Config.php's constructor - a foundational
 * singleton used throughout every consuming app - just to make this one
 * class test-friendly, seed its private static instance directly via
 * reflection, bypassing the file-reading constructor entirely. This is still
 * the real Config class with real get() behavior; only its private state is
 * set directly instead of built through the constructor.
 */
class JsonTest extends TestCase
{

    protected function setUp(): void
    {
        $this->seedConfig(array());
    }

    protected function tearDown(): void
    {
        // don't leak the seeded singleton into other test classes that might
        // expect Config::getInstance() to behave normally (or not be called
        // at all)
        $this->seedConfig(null, reset: true);
        unset($_SERVER['HTTP_ORIGIN']);
    }

    public function testInitResponseEncodesTheGivenInfoAsJson(): void
    {
        $json     = new Json();
        $response = $json->initResponse(array('title' => 'Cuina de Profit', 'count' => 3));

        $this->assertSame('{"title":"Cuina de Profit","count":3}', (string) $response->getBody());
    }

    public function testInitResponseEncodesAnEmptyArrayAsAnEmptyJsonArray(): void
    {
        $json     = new Json();
        $response = $json->initResponse(array());

        $this->assertSame('[]', (string) $response->getBody());
    }

    public function testGetReturnsThePreviouslyBuiltResponse(): void
    {
        $json = new Json();
        $json->initResponse(array('a' => 1));

        $this->assertSame('{"a":1}', (string) $json->get()->getBody());
    }

    public function testSetsContentTypeToJson(): void
    {
        $response = (new Json())->initResponse(array());

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testAllowsAnyOriginWhenNoAllowlistIsConfigured(): void
    {
        // default config seeded in setUp() has no 'api.allow-origin' entry
        $response = (new Json())->initResponse(array());

        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testAllowsAConfiguredOriginThatMatchesTheRequest(): void
    {
        $this->seedConfig(array('api' => array('allow-origin' => array('https://example.com'))));
        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';

        $response = (new Json())->initResponse(array());

        $this->assertSame('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testSetsNoAllowOriginHeaderForAnUnlistedOrigin(): void
    {
        // regression-shaped check on existing behavior: when an allowlist is
        // configured and the request's origin isn't in it, the loop finds no
        // match and falls through without a fallback '*' either
        $this->seedConfig(array('api' => array('allow-origin' => array('https://example.com'))));
        $_SERVER['HTTP_ORIGIN'] = 'https://not-allowed.example';

        $response = (new Json())->initResponse(array());

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    private function seedConfig(?array $config, bool $reset = false): void
    {
        $reflection       = new ReflectionClass(Config::class);
        $instanceProperty = $reflection->getProperty('instance');

        if ($reset) {
            $instanceProperty->setValue(null, null);
            return;
        }

        $instance       = $reflection->newInstanceWithoutConstructor();
        $configProperty = $reflection->getProperty('config');
        $configProperty->setValue($instance, $config);

        $instanceProperty->setValue(null, $instance);
    }

}
