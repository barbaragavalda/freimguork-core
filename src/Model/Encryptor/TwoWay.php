<?php

namespace Core\Model\Encryptor;

/**
 * Class TwoWay
 * encrypts a string that will not be decrypted
 * @package Core\Model\Encryptor
 * @author  Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date    26/10/2017
 */
class TwoWay
{

    const METHOD = 'AES-128-CTR';

    /**
     * set encryption key. If you don't supply your own key, this will be the default
     *
     * @param bool $key
     *
     * @return bool|string
     */
    private static function initKey($key = false)
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

    private static function iv_bytes()
    {
        return openssl_cipher_iv_length(self::METHOD);
    }

    /**
     * @param string $string to be encrypted
     * @param string $key    key used to encrypt
     *
     * @return string           encrypted string
     */
    public static function encrypt($string, $key)
    {
        $key = self::initKey($key);

        $iv               = openssl_random_pseudo_bytes(self::iv_bytes());
        $encrypted_string = bin2hex($iv) . openssl_encrypt($string, self::METHOD, $key, 0, $iv);
        return $encrypted_string;
    }

    /**
     * decrypt string
     *
     * @param string $encrypted encrypted string
     * @param string $key       key used to encrypt
     *
     * @return string|bool          decrypted string or false
     */
    public static function decrypt($encrypted, $key)
    {
        $key = self::initKey($key);

        $iv_strlen = 2 * self::iv_bytes();
        if ($encrypted) {
            if (preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $encrypted, $regs)) {
                list(, $iv, $crypted_string) = $regs;
                $decrypted_string = openssl_decrypt($crypted_string, self::METHOD, $key, 0, hex2bin($iv));
                return $decrypted_string;
            }
        }

        return false;
    }

}