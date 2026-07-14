<?php

namespace Core\Model\Encryptor;

/**
 * Class OneWay
 * hashes a string that will never be decrypted (e.g. a password).
 *
 * New hashes use password_hash() (Argon2id, falling back to bcrypt on PHP
 * builds without Argon2 support) over an HMAC-SHA256 "pepper" derived from
 * the application's master secret (see Secret::derive()) - unlike the
 * previous crypt() SHA-512 scheme, a leaked hash can't be attacked without
 * that secret. Existing hashes from the previous scheme (recognizable by
 * their '$6$' crypt() prefix) keep verifying correctly forever, since a
 * one-way hash can never be re-encrypted without the original plaintext -
 * see needsRehash() for the lazy, verify-then-upgrade migration path.
 */
class OneWay
{

    private const string LEGACY_MARKER = '$6$';

    /**
     * @param string $string  to be hashed
     * @param string $context per-value context (e.g. "<id>_<created>_<field>")
     *
     * @return string hashed string
     */
    public static function encrypt(string $string, string $context): string
    {
        return password_hash(self::pepper($string, $context), self::algo());
    }

    /**
     * checks if the unencrypted string matches the hash, whichever format it's in
     *
     * @param string $encrypted   hashed string
     * @param string $unencrypted string to be checked
     * @param string $context     context used when the hash was created
     *
     * @return bool
     */
    public static function check(string $encrypted, string $unencrypted, string $context): bool
    {
        if (!$encrypted || !$unencrypted) {
            return false;
        }

        if (self::isLegacy($encrypted)) {
            return self::legacyCheck($encrypted, $unencrypted, $context);
        }

        return password_verify(self::pepper($unencrypted, $context), $encrypted);
    }

    /**
     * true if $encrypted should be replaced with a fresh hash next time the
     * plaintext is available (a successful check()) - either because it's
     * still in the legacy crypt() format, or because password_hash()'s own
     * cost parameters have since increased.
     */
    public static function needsRehash(string $encrypted): bool
    {
        if (self::isLegacy($encrypted)) {
            return true;
        }
        return password_needs_rehash($encrypted, self::algo());
    }

    /**
     * true if this hash predates the password_hash()-based format
     */
    public static function isLegacy(string $value): bool
    {
        return str_starts_with($value, self::LEGACY_MARKER);
    }

    private static function algo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    private static function pepper(string $string, string $context): string
    {
        $key = Secret::derive('freimguork:oneway:' . $context);
        return hash_hmac('sha256', $context . "\0" . $string, $key);
    }

    //*******************************************//
    //************** L E G A C Y *****************//
    //*******************************************//
    // preserved exactly as the original algorithm so pre-migration hashes keep verifying.

    private static function legacyInitKey(string $key = ''): string
    {
        if (!$key) {
            $key = gethostname() . "|" . ip2long($_SERVER['SERVER_ADDR']);
        }
        return '$6$rounds=5000$' . md5($key) . '$';
    }

    private static function legacyEncrypt(string $string, string $key): string
    {
        $key = self::legacyInitKey($key);
        return crypt($string, $key);
    }

    private static function legacyCheck(string $encrypted, string $unencrypted, string $context): bool
    {
        return hash_equals($encrypted, self::legacyEncrypt($unencrypted, $context));
    }

}
