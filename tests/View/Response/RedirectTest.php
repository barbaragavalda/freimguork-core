<?php

namespace Core\Tests\View\Response;

use Core\View\Response\Redirect;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Only the IS_DEV=true branch of Redirect::initResponse() is reachable here -
 * IS_DEV is a global constant defined once in tests/bootstrap.php, so the
 * else branch (setHeaderLocation() for a real prod redirect) can't be
 * exercised in this process. See tests/bootstrap.php for why
 * @runInSeparateProcess can't work around that either.
 */
class RedirectTest extends TestCase
{

    private array $originalServer;

    protected function setUp(): void
    {
        $this->originalServer                = $_SERVER;
        $_SERVER['REQUEST_SCHEME']            = 'https';
        $_SERVER['HTTP_HOST']                 = 'example.com';
        $_SERVER['REQUEST_URI']               = '/old-path';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    public function testThrowsOnAnEmptyUrl(): void
    {
        // Redirect.php imports the plain global \Exception, not
        // Core\Utils\Exception, despite the rest of the framework's
        // convention - asserting the real thrown type
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot redirect to an empty URL.');

        (new Redirect('', 301))->initResponse();
    }

    public function testDevModeBodyEscapesTheTargetUrl(): void
    {
        $html = (new Redirect('http://example.com/?a=1&b=2', 301))->initResponse();

        $this->assertStringContainsString('http://example.com/?a=1&amp;b=2', $html);
        $this->assertStringNotContainsString('a=1&b=2"', $html);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function statusMessageProvider(): array
    {
        return array(
            'moved permanently'  => array(301, 'Moved Permanently'),
            'moved temporarily'  => array(302, 'Moved Temporarily'),
            'unauthorized'       => array(401, 'Unauthorized'),
            'forbidden'          => array(403, 'Forbidden'),
            'unmapped status'    => array(500, 'Undefined message'),
        );
    }

    #[DataProvider('statusMessageProvider')]
    public function testDevModeBodyIncludesTheStatusMessage(int $status, string $expectedMessage): void
    {
        $html = (new Redirect('http://example.com/', $status))->initResponse();

        $this->assertStringContainsString("$status $expectedMessage", $html);
    }

}
