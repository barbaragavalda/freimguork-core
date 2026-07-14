<?php

namespace Core\Tests\Model\Encryptor;

use Core\Model\Encryptor\Secret;
use Core\Model\Encryptor\TwoWay;
use PHPUnit\Framework\TestCase;

class TwoWayTest extends TestCase
{

    protected function setUp(): void
    {
        Secret::setForTesting(bin2hex(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        Secret::setForTesting(null);
    }

    public function testEncryptsAndDecryptsRoundTrip(): void
    {
        $context   = '1_2024-01-01 00:00:00_email';
        $encrypted = TwoWay::encrypt('hello@example.com', $context);

        $this->assertNotSame('hello@example.com', $encrypted);
        $this->assertSame('hello@example.com', TwoWay::decrypt($encrypted, $context));
    }

    public function testNewFormatIsNotFlaggedAsLegacy(): void
    {
        $this->assertFalse(TwoWay::isLegacy(TwoWay::encrypt('value', 'context')));
    }

    public function testEncryptingTheSameValueTwiceProducesDifferentCiphertext(): void
    {
        // random nonce per call - guards against ever reintroducing a
        // deterministic scheme for the actual ciphertext (searchability is
        // BlindIndex's job, not TwoWay's)
        $context = '1_2024-01-01 00:00:00_email';

        $this->assertNotSame(
            TwoWay::encrypt('hello@example.com', $context),
            TwoWay::encrypt('hello@example.com', $context)
        );
    }

    public function testDecryptingWithTheWrongContextFails(): void
    {
        $encrypted = TwoWay::encrypt('hello@example.com', 'context-a');
        $this->assertFalse(TwoWay::decrypt($encrypted, 'context-b'));
    }

    public function testDecryptsValuesWrittenByThePreMigrationLegacyAlgorithm(): void
    {
        $context = '1_2024-01-01 00:00:00_email';
        $legacy  = $this->legacyEncrypt('hello@example.com', $context);

        $this->assertTrue(TwoWay::isLegacy($legacy));
        $this->assertSame('hello@example.com', TwoWay::decrypt($legacy, $context));
    }

    /**
     * reimplements the exact pre-migration algorithm (plain AES-128-CTR keyed
     * only by $context, no application secret at all) to produce a legacy
     * fixture without hardcoding a brittle magic string.
     */
    private function legacyEncrypt(string $value, string $context): string
    {
        $key = ctype_print($context) ? openssl_digest($context, 'SHA256', true) : $context;
        $iv  = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-128-CTR'));

        return bin2hex($iv) . openssl_encrypt($value, 'AES-128-CTR', $key, 0, $iv);
    }

}
