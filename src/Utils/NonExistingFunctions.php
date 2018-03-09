<?php

if(!function_exists('array_column')) {
    function array_column($array,$column_name){
        return array_map(function($element) use($column_name){return $element[$column_name];}, $array);
    }
}

if(!function_exists('hash_equals')) {
    function hash_equals($str1, $str2) {
        if(strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
            return !$ret;
        }
    }
}

if(!function_exists('hex2bin')){
    function hex2bin( $str ) {
        $sbin = '';
        $len = strlen( $str );
        for ( $i = 0; $i < $len; $i += 2 ) {
            $sbin .= pack( 'H*', substr( $str, $i, 2 ) );
        }

        return $sbin;
    }
}

if(!function_exists('getallheaders')){
    function getallheaders(){
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}