<?php

namespace Core\Model\Push;

use Core\Utils\Config;

class Android extends Base {

    const URL = 'https://android.googleapis.com/gcm/send';

    private $API_KEY = null;
    private $APP_NAME = null;

    private $headers = array();
    private $fields = array();
    private $ch = null;

    public function __construct( $message, $tokens, $urlScheme = '' ){
        $config = Config::getInstance();
        $pushConfig = $config->get('push');
        $this->API_KEY = $pushConfig['android_key'];
        $this->APP_NAME = $pushConfig['android_app_name'];

        $this->headers = array(
            'Authorization: key=' . $this->API_KEY,
            'Content-Type: application/json'
        );
        $this->fields = array(
            'registration_ids'  => $tokens,
            'data'              => array( "title" => $this->APP_NAME, "message" => $message )
        );
        if( $urlScheme ){
            $this->fields['data']['link'] = $urlScheme;
        }
    }

    public function send(){
        // Open connection
        $this->ch = curl_init();
        curl_setopt( $this->ch, CURLOPT_URL, self::URL);
        curl_setopt( $this->ch, CURLOPT_POST, true );
        curl_setopt( $this->ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, json_encode( $this->fields ) );

        // Execute post
        $result = curl_exec($this->ch);
        $this->result = array(
            'ok' => $result['success'],
            'ko' => $result['failure'],
        );

    }

    public function close(){
        //close socket
        curl_close($this->ch);
    }
}