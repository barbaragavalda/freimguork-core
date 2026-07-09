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

    public function testNoPrefixWhenScriptIsAtTheWebRoot(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->assertSame('', Config::getWebFolderPrefix());
    }

    public function testPrefixIncludesActualFolderNameWhenScriptIsInASubfolder(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/public/index.php';

        $this->assertSame('public/', Config::getWebFolderPrefix());
    }

    public function testWorksRegardlessOfWebFolderName(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/web/index.php';

        $this->assertSame('web/', Config::getWebFolderPrefix());
    }

    public function testIgnoresContainerFilesystemPaths(): void
    {
        // regression: DOCUMENT_ROOT/SCRIPT_FILENAME are filesystem paths that can
        // live in different namespaces than each other when Apache and PHP-FPM
        // run in separate containers (this project's own local dev VM does this)
        // - getWebFolderPrefix() must not depend on them at all
        $_SERVER['SCRIPT_NAME']     = '/index.php';
        $_SERVER['DOCUMENT_ROOT']   = '/usr/local/apache2/htdocs/app/web';
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/html/app/web/index.php';

        $this->assertSame('', Config::getWebFolderPrefix());
    }

}
