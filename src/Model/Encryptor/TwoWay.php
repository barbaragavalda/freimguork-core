<?php

namespace Core\Model\Encryptor;

/**
 * Class TwoWay
 * encrypts a string that can be decrypted later.
 *
 * Values are AES-256-GCM (authenticated) under a key derived from the
 * application's master secret (see Secret::derive()) mixed with a per-value
 * context, and are versioned with a '$gcm256$' prefix so they're always
 * distinguishable from values written by the previous, pre-migration
 * algorithm (plain AES-128-CTR keyed only by the context itself, with no
 * application secret involved at all - anyone with DB read access could
 * reconstruct that "key"). decrypt() auto-detects which format a given value
 * is in, so rows can be migrated gradually (see Migrator) without breaking
 * reads in the meantime.
 */
class TwoWay
{

    private const string METHOD = 'aes-256-gcm';
    private const string LEGACY_METHOD = 'AES-128-CTR';
    private const string PREFIX = '$gcm256$';
    private const int NONCE_BYTES = 12;
    private const int TAG_BYTES = 16;

    /**
     * @param string $string  to be encrypted
     * @param string $context per-value context (e.g. "<id>_<created>_<field>")
     *
     * @return string encrypted string
     */
    public static function encrypt(string $string, string $context): string
    {
        $key   = Secret::derive('freimguork:twoway:' . $context);
        $nonce = openssl_random_pseudo_bytes(self::NONCE_BYTES);

        $tag        = '';
        $ciphertext = openssl_encrypt($string, self::METHOD, $key, OPENSSL_RAW_DATA, $nonce, $tag);

        return self::PREFIX . bin2hex($nonce) . bin2hex($tag) . bin2hex($ciphertext);
    }

    /**
     * decrypt string, dispatching to whichever format it was encrypted with
     *
     * @param string $encrypted encrypted string
     * @param string $context   per-value context used at encryption time
     *
     * @return string|bool decrypted string or false
     */
    public static function decrypt(string $encrypted, string $context): string|bool
    {
        if (!$encrypted) {
            return false;
        }

        if (self::isLegacy($encrypted)) {
            return self::legacyDecrypt($encrypted, $context);
        }

        $body          = substr($encrypted, strlen(self::PREFIX));
        $nonceHex      = substr($body, 0, self::NONCE_BYTES * 2);
        $tagHex        = substr($body, self::NONCE_BYTES * 2, self::TAG_BYTES * 2);
        $ciphertextHex = substr($body, self::NONCE_BYTES * 2 + self::TAG_BYTES * 2);

        $nonce      = hex2bin($nonceHex);
        $tag        = hex2bin($tagHex);
        $ciphertext = hex2bin($ciphertextHex);
        if ($nonce === false || $tag === false || $ciphertext === false) {
            return false;
        }

        $key = Secret::derive('freimguork:twoway:' . $context);
        return openssl_decrypt($ciphertext, self::METHOD, $key, OPENSSL_RAW_DATA, $nonce, $tag);
    }

    /**
     * true if this value predates the '$gcm256$' format and still needs migrating (see Migrator)
     */
    public static function isLegacy(string $value): bool
    {
        return !str_starts_with($value, self::PREFIX);
    }

    //*******************************************//
    //************** L E G A C Y *****************//
    //*******************************************//
    // preserved exactly as the original algorithm so pre-migration values keep decrypting.

    private static function legacyInitKey(string $key = ''): string
    {
        if (!$key) {
            $key = gethostname() . "|" . ip2long($_SERVER['SERVER_ADDR']);
        }
        if (ctype_print($key)) {
            // convert key to binary format
            $key = openssl_digest($key, 'SHA256', true);
        }

        return $key;
    }

    private static function legacyIvBytes(): int
    {
        return openssl_cipher_iv_length(self::LEGACY_METHOD);
    }

    private static function legacyDecrypt(string $encrypted, string $context): string|bool
    {
        $key = self::legacyInitKey($context);

        $ivStrlen = 2 * self::legacyIvBytes();
        if (preg_match('/^(.{' . $ivStrlen . '})(.+)$/', $encrypted, $regs)) {
            list(, $iv, $cryptedString) = $regs;
            return openssl_decrypt($cryptedString, self::LEGACY_METHOD, $key, 0, hex2bin($iv));
        }

        return false;
    }

}
