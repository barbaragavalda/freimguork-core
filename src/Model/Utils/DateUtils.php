<?php

namespace Core\Model\Utils;

class DateUtils {

    const FORMAT_TIMESTAMP_DB = 'Y-m-d H:i:s';
    const FORMAT_TIMESTAMP_USER = 'H:i:s d/m/Y';

    public static function dmyFormat($string){
        try{
            $date = new \DateTime($string);
            return $date->format('d/m/Y');
        }catch(\Exception $e){
            return '-';
        }
    }

    public static function userTimestamp($string){
        try{
            $date = \DateTime::createFromFormat(self::FORMAT_TIMESTAMP_DB, $string);
            return $date->format(self::FORMAT_TIMESTAMP_USER);
        }catch(\Exception $e){
            return '-';
        }
    }

    public static function databaseTimestamp($string){
        try{
            $date = \DateTime::createFromFormat(self::FORMAT_TIMESTAMP_USER, $string);
            return $date->format(self::FORMAT_TIMESTAMP_DB);
        }catch(\Exception $e){
            return '';
        }
    }

}