<?php

namespace Core\Model\Utils;

class Curl
{

    const GET  = 'GET';
    const POST = 'POST';

    public static function get($url, $params = array(), $header = null, $path = null)
    {
        return self::make(self::GET, $url, $params, $header, $path);
    }

    public static function post($url, $params = array(), $header = null, $path = null)
    {
        return self::make(self::POST, $url, $params, $header, $path);
    }

    /**
     * do curl request
     *
     * @param string $method GET | POST
     * @param string $url    request URL
     * @param array  $params request params
     * @param array  $header custom headers
     * @param string $path   log file
     *
     * @return array|mixed
     */
    private static function make($method, $url, $params = array(), $header = null, $path = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if (IS_DEV) {
            curl_setopt($curl, CURLOPT_PROXY, $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT']);
        }

        if ($method == self::POST) {
            curl_setopt($curl, CURLOPT_POST, 1);
            if (count($params)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
            }
        } else {
            if (count($params)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            }
        }

        if ($header != null) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

        $result = array();
        $output = curl_exec($curl);
        if (!curl_errno($curl)) {
            if ($path != null) {
                file_put_contents($path, $output);
            }
            $result = json_decode($output, true);
        }

        curl_close($curl);
        return $result;
    }

}
