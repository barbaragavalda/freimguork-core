<?php

namespace Core\Model\Encryptor;


class OneWay {

    private static function initKey($key = false){
        if(!$key) {
            // if you don't supply your own key, this will be the default
            $key = gethostname() . "|" . ip2long($_SERVER['SERVER_ADDR']);
        }
        $key = '$6$rounds=5000$' . md5($key) . '$';

        return $key;
    }


    public static function encrypt($string, $key){
        $key = self::initKey($key);
        return crypt($string, $key);
    }

    public static function check($encrypted, $unencrypted, $key){
        if( $encrypted && $unencrypted ){
            if( hash_equals($encrypted, self::encrypt($unencrypted, $key)) ){
                return true;
            }
        }
        return false;
    }

}
