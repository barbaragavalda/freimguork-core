<?php

namespace Core\Model\Encryptor;

/**
 * Class TwoWay
 * encrypts a string that will not be decrypted
 */
class TwoWay
{

    const string METHOD = 'AES-128-CTR';

    /**
     * set encryption key. If you don't supply your own key, this will be the default
     *
     * @param string $key
     *
     * @return string
     */
    private static function initKey(string $key = ''): string
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

    private static function iv_bytes(): string
    {
        return openssl_cipher_iv_length(self::METHOD);
    }

    /**
     * @param string $string to be encrypted
     * @param string $key    key used to encrypt
     *
     * @return string           encrypted string
     */
    public static function encrypt(string $string, string $key): string
    {
        $key = self::initKey($key);

        $iv = openssl_random_pseudo_bytes(self::iv_bytes());
        return bin2hex($iv) . openssl_encrypt($string, self::METHOD, $key, 0, $iv);
    }

    /**
     * decrypt string
     *
     * @param string $encrypted encrypted string
     * @param string $key       key used to encrypt
     *
     * @return string|bool          decrypted string or false
     */
    public static function decrypt(string $encrypted, string $key): string|bool
    {
        $key = self::initKey($key);

        $iv_strlen = 2 * self::iv_bytes();
        if ($encrypted) {
            if (preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $encrypted, $regs)) {
                list(, $iv, $crypted_string) = $regs;
                return openssl_decrypt($crypted_string, self::METHOD, $key, 0, hex2bin($iv));
            }
        }

        return false;
    }

}