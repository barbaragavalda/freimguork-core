<?php

namespace Core\Tests\Utils;

use Core\Utils\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{

    private ?array $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    public function testNoPrefixWhenDocumentRootIsTheWebFolder(): void
    {
        $_SERVER['SCRIPT_FILENAME'] = '/home/user/web/index.php';
        $_SERVER['DOCUMENT_ROOT']   = '/home/user/web';

        $this->assertSame('', Config::getWebFolderPrefix());
    }

    public function testPrefixIncludesActualFolderNameWhenDocumentRootIsAnAncestor(): void
    {
        $_SERVER['SCRIPT_FILENAME'] = '/home/user/app/public/index.php';
        $_SERVER['DOCUMENT_ROOT']   = '/home/user/app';

        $this->assertSame('public/', Config::getWebFolderPrefix());
    }

    public function testWorksRegardlessOfWebFolderName(): void
    {
        $_SERVER['SCRIPT_FILENAME'] = '/home/user/app/web/index.php';
        $_SERVER['DOCUMENT_ROOT']   = '/home/user/app';

        $this->assertSame('web/', Config::getWebFolderPrefix());
    }

    public function testIgnoresTrailingSlashDifferences(): void
    {
        $_SERVER['SCRIPT_FILENAME'] = '/home/user/web/index.php';
        $_SERVER['DOCUMENT_ROOT']   = '/home/user/web/';

        $this->assertSame('', Config::getWebFolderPrefix());
    }

}
