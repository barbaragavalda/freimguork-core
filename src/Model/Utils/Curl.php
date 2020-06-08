<?php

namespace Core\Model\Utils;

class Curl {

    /**
     * do curl request
     * @param string $url       request URL
     * @param array $params     request params
     * @param array $header     custom headers
     * @param string $path      log file
     * @return array|mixed
     */
    public static function get($url, $params = array(), $header = null, $path = null){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if( count($params) ){
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        if( $header != null ){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

        $result = array();
        $output = curl_exec($curl);
        if( !curl_errno($curl) ){
            if( $path != null ){
                file_put_contents($path, $output);
            }
            $result = json_decode($output, true);
        }

        curl_close($curl);
        return $result;
    }

}
