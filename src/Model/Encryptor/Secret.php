<?php

namespace Core\Model\Encryptor;

use Core\Utils\Config;
use RuntimeException;

/**
 * Class Secret
 * resolves the application's master encryption secret and derives per-purpose
 * subkeys from it via HKDF, so OneWay/TwoWay/BlindIndex never need to share
 * a single key across unrelated purposes.
 */
class Secret
{

    private static ?string $master = null;

    private static ?string $testHex = null;

    /**
     * raw master secret bytes, read from config/keys.php: ['encryption' => ['secret' => '<64 hex chars>']]
     *
     * @throws RuntimeException if not configured
     */
    public static function master(): string
    {
        if (self::$master !== null) {
            return self::$master;
        }

        $hex = self::$testHex ?? Config::getInstance()->get('encryption', 'secret');
        if (!$hex || !is_string($hex)) {
            throw new RuntimeException(
                "Missing encryption secret. Set config/keys.php: \$config['encryption']['secret'] "
                . 'to 64 hex characters (32 random bytes, e.g. bin2hex(random_bytes(32))).'
            );
        }

        $bytes = @hex2bin($hex);
        if ($bytes === false || strlen($bytes) < 32) {
            throw new RuntimeException(
                "Invalid encryption secret in config/keys.php: expected 64 hex characters (32 bytes)."
            );
        }

        self::$master = $bytes;
        return self::$master;
    }

    /**
     * derive a purpose-specific subkey from the master secret via HKDF-SHA256
     *
     * @param string $info domain-separation label, e.g. 'freimguork:twoway:' . $context
     */
    public static function derive(string $info): string
    {
        return hash_hkdf('sha256', self::master(), 32, $info);
    }

    /**
     * @internal test-only seam. Production code must configure the secret via
     * config/keys.php (see master()) - this exists because Config::getInstance()
     * needs a real config/projects.php under DIR_ROOT, which this package (a
     * library, never run standalone) doesn't have one of.
     */
    public static function setForTesting(?string $hex): void
    {
        self::$testHex = $hex;
        self::$master  = null;
    }

}
