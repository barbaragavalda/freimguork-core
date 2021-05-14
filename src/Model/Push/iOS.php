<?php

namespace Core\Model\Push;

use Core\Utils\Config;

abstract class iOS extends Base {

    protected $APNS_PORT = 443;
    protected $APNS_HOST = null;
    protected $APNS_CERT = null;
    protected $APNS_PASSWORD = null;

    protected $appName = '';
    protected $appBundle = '';

    protected $apns = false;
    protected $currentSocket = null;

    public function __construct($host, $port = 443){
        parent::__construct();

        $config = Config::getInstance();
        $pushConfig = $config->get('push');
        $this->APNS_PORT = $port;
        $this->APNS_HOST = $host;
        $this->APNS_CERT = $pushConfig['ios_cert'];
        $this->APNS_PASSWORD = $pushConfig['ios_password'];

        $webserviceConfig = $config->get('webservice');
        $this->appName = $webserviceConfig['app_name'];
        $this->appBundle = $webserviceConfig['ios_bundle'];
    }

    protected function open(){
        if (!defined('CURL_HTTP_VERSION_2_0')) {
            define('CURL_HTTP_VERSION_2_0', 3);
        }
        $this->currentSocket = curl_init();
        curl_setopt($this->currentSocket, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    }

    public function close(){
        curl_close($this->currentSocket);
    }

}