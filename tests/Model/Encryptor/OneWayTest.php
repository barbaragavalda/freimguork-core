<?php

namespace Core\Tests\Model\Encryptor;

use Core\Model\Encryptor\OneWay;
use Core\Model\Encryptor\Secret;
use PHPUnit\Framework\TestCase;

class OneWayTest extends TestCase
{

    protected function setUp(): void
    {
        Secret::setForTesting(bin2hex(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        Secret::setForTesting(null);
    }

    public function testHashesAndVerifiesRoundTrip(): void
    {
        $context = '1_2024-01-01 00:00:00_password';
        $hash    = OneWay::encrypt('correct horse battery staple', $context);

        $this->assertTrue(OneWay::check($hash, 'correct horse battery staple', $context));
        $this->assertFalse(OneWay::check($hash, 'wrong password', $context));
    }

    public function testFreshHashesDoNotNeedRehashing(): void
    {
        $this->assertFalse(OneWay::needsRehash(OneWay::encrypt('password', 'context')));
    }

    public function testVerifiesHashesWrittenByThePreMigrationLegacyAlgorithm(): void
    {
        $context = '1_2024-01-01 00:00:00_password';
        $legacy  = $this->legacyEncrypt('correct horse battery staple', $context);

        $this->assertTrue(OneWay::isLegacy($legacy));
        $this->assertTrue(OneWay::check($legacy, 'correct horse battery staple', $context));
        $this->assertFalse(OneWay::check($legacy, 'wrong password', $context));
    }

    public function testLegacyHashesNeedRehashing(): void
    {
        $this->assertTrue(OneWay::needsRehash($this->legacyEncrypt('password', 'context')));
    }

    /**
     * reimplements the exact pre-migration algorithm (crypt() SHA-512,
     * rounds=5000, salt from md5($context) alone) to produce a legacy
     * fixture without hardcoding a brittle magic string.
     */
    private function legacyEncrypt(string $string, string $context): string
    {
        return crypt($string, '$6$rounds=5000$' . md5($context) . '$');
    }

}
