<?php

namespace Core\Model\Utils;

class DateUtils {

    public static function dmyFormat($string){
        try{
            $date = new \DateTime($string);
            return $date->format('d/m/Y');
        }catch(\Exception $e){
            return '-';
        }
    }

    public static function hisDmyFormat($string){
        try{
            $date = new \DateTime($string);
            return $date->format('H:i:s d/m/Y');
        }catch(\Exception $e){
            return '-';
        }
    }

}