<?php

namespace Core\Model\Utils;

class DateUtils {

    const FORMAT_DATE_DB = 'Y-m-d';
    const FORMAT_DATE_USER = 'd/m/Y';
    const FORMAT_TIMESTAMP_DB = 'Y-m-d H:i:s';
    const FORMAT_TIMESTAMP_USER = 'H:i:s d/m/Y';

    public static function userDate($string){
        $value = self::format(self::FORMAT_DATE_DB, self::FORMAT_DATE_USER, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    public static function databaseDate($string){
        $value = self::format(self::FORMAT_DATE_USER, self::FORMAT_DATE_DB, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    public static function userTimestamp($string){
        $value = self::format(self::FORMAT_TIMESTAMP_DB, self::FORMAT_TIMESTAMP_USER, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    public static function databaseTimestamp($string){
        $value = self::format(self::FORMAT_TIMESTAMP_USER, self::FORMAT_TIMESTAMP_DB, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    private function format($from, $to, $string){
        if( !empty($string) && is_string($string) ){
            try{
                $date = \DateTime::createFromFormat($from, $string);
                return $date->format($to);
            }catch(\Exception $e){
                return false;
            }
        }
        return false;
    }

}