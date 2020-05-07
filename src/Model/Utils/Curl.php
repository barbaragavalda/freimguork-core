<?php

namespace Core\Model\Utils;

class Curl {

    public static function get($url, $header = null){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if( $header != null ){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

        $result = array();
        $output = curl_exec($curl);
        if( !curl_errno($curl) ){
            $result = json_decode($output, true);
        }

        curl_close($curl);
        return $result;
    }

}
