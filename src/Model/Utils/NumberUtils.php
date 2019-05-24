<?php

namespace Core\Model\Utils;

class NumberUtils {

    public static function distance($lat1, $long1, $lat2, $long2){
        if( $lat1 && $long1 && $lat2 && $long2 ){
            return round(6371 * acos(
                    cos(deg2rad($lat1))
                    * cos(deg2rad($lat2))
                    * cos(deg2rad($long2) - deg2rad($long1)) + sin(deg2rad($lat1))
                    * sin(deg2rad($lat2))
                ), 2);
        }
        return null;
    }

}