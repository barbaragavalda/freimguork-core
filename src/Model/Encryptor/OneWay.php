<?php

namespace Core\Model\Encryptor;

/**
 * Class OneWay
 *
 * encrypts a string that will not be decrypted
 *
 * @package Core\Model\Encryptor
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
class OneWay {

    /**
     * set encryption key. If you don't supply your own key, this will be the default
     * @param bool $key
     * @return bool|string
     */
    private static function initKey($key = false){
        if( !$key ){
            $key = gethostname() . "|" . ip2long($_SERVER['SERVER_ADDR']);
        }
        $key = '$6$rounds=5000$' . md5($key) . '$';

        return $key;
    }

    /**
     * @param string $string    to be encrypted
     * @param string $key       key used to encrypt
     * @return string           encrypted string
     */
    public static function encrypt($string, $key){
        $key = self::initKey($key);
        return crypt($string, $key);
    }

    /**
     * checks if the unencrypted string is the same as encrypted string
     * @param string $encrypted     encrypted string
     * @param string $unencrypted   string to be checked
     * @param string $key           key used to encrypt
     * @return bool
     */
    public static function check($encrypted, $unencrypted, $key){
        if( $encrypted && $unencrypted ){
            if( hash_equals($encrypted, self::encrypt($unencrypted, $key)) ){
                return true;
            }
        }
        return false;
    }

}
