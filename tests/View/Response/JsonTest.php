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
 *
 * Header assertions aren't possible here: PHP's CLI SAPI (what PHPUnit runs
 * under) doesn't populate headers_list() from header() calls, so Json's
 * Access-Control-Allow-Origin/Content-Type logic isn't observable in this
 * process - only initResponse()'s return value is.
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
    }

    public function testInitResponseEncodesTheGivenInfoAsJson(): void
    {
        $json = new Json();

        $this->assertSame(
            '{"title":"Cuina de Profit","count":3}',
            $json->initResponse(array('title' => 'Cuina de Profit', 'count' => 3))
        );
    }

    public function testInitResponseEncodesAnEmptyArrayAsAnEmptyJsonArray(): void
    {
        $json = new Json();

        $this->assertSame('[]', $json->initResponse(array()));
    }

    public function testGetReturnsThePreviouslyBuiltResponse(): void
    {
        $json = new Json();
        $json->initResponse(array('a' => 1));

        $this->assertSame('{"a":1}', $json->get());
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
