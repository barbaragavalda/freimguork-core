<?php

namespace Core\Utils;


class StringUtils{

    static function startsWidth($haystack, $needle){
        return strpos($haystack, $needle) === 0;
    }

    static function endsWidth($haystack, $needle){
        $length = strlen($needle);
        return $length === 0 || (substr($haystack, -$length) === $needle);
    }

}