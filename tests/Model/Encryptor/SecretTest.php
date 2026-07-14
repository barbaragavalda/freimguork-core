<?php

namespace Core\Tests\Model\Encryptor;

use Core\Model\Encryptor\Secret;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SecretTest extends TestCase
{

    protected function tearDown(): void
    {
        Secret::setForTesting(null);
    }

    public function testDerivesTheSameSubkeyTwiceForTheSameInfo(): void
    {
        Secret::setForTesting(bin2hex(random_bytes(32)));

        $this->assertSame(Secret::derive('a'), Secret::derive('a'));
    }

    public function testDerivesDifferentSubkeysForDifferentInfo(): void
    {
        Secret::setForTesting(bin2hex(random_bytes(32)));

        $this->assertNotSame(Secret::derive('a'), Secret::derive('b'));
    }

    public function testThrowsWhenSecretIsMissing(): void
    {
        // an empty string bypasses Config::getInstance() (via setForTesting())
        // and hits master()'s own "not configured" validation directly
        Secret::setForTesting('');

        $this->expectException(RuntimeException::class);
        Secret::master();
    }

    public function testThrowsWhenSecretIsTooShort(): void
    {
        Secret::setForTesting(bin2hex(random_bytes(8)));

        $this->expectException(RuntimeException::class);
        Secret::master();
    }

    public function testThrowsWhenSecretIsNotValidHex(): void
    {
        Secret::setForTesting('not hex at all');

        $this->expectException(RuntimeException::class);
        Secret::master();
    }

}
