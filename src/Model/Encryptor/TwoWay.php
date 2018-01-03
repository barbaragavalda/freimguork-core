<?php

namespace Core\Model\Encryptor;


class TwoWay {

    const METHOD = 'AES-128-CTR';

    private static function initKey($key = false){
        if(!$key) {
            // if you don't supply your own key, this will be the default
            $key = gethostname() . "|" . ip2long($_SERVER['SERVER_ADDR']);
        }
        if(ctype_print($key)) {
            // convert key to binary format
            $key = openssl_digest($key, 'SHA256', true);
        }

        return $key;
    }

    private static function iv_bytes() {
        return openssl_cipher_iv_length(self::METHOD);
    }

    public static function encrypt($string, $key){
        $key = self::initKey($key);

        $iv = openssl_random_pseudo_bytes(self::iv_bytes());
        $encrypted_string = bin2hex($iv) . openssl_encrypt($string, self::METHOD, $key, 0, $iv);
        return $encrypted_string;
    }

    public static function decrypt($encrypted, $key){
        $key = self::initKey($key);

        $iv_strlen = 2  * self::iv_bytes();
        if( preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $encrypted, $regs) ){
            list(, $iv, $crypted_string) = $regs;
            $decrypted_string = openssl_decrypt($crypted_string, self::METHOD, $key, 0, hex2bin($iv));
            return $decrypted_string;
        }

        return false;
    }

}