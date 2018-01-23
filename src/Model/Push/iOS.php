<?php

namespace Core\Model\Push;

use Core\Utils\Config;

class iOS extends Base {

    const APNS_PORT = 2195;

    private $APNS_HOST = null;
    private $APNS_CERT = null;
    private $APNS_PASSWORD = null;

    private $apns = null;
    private $payload = null;
    private $tokens = null;

    public function __construct( $message, $tokens, $urlScheme = '' ){
        $this->tokens = $tokens;

        $config = Config::getInstance();
        $pushConfig = $config->get('push');
        $this->APNS_HOST = $pushConfig['ios_host'];
        $this->APNS_CERT = $pushConfig['ios_cert'];
        $this->APNS_PASSWORD = $pushConfig['ios_password'];

        //open socket
        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $this->APNS_CERT);
        stream_context_set_option($streamContext, 'ssl', 'passphrase', $this->APNS_PASSWORD);
        $this->apns = stream_socket_client('ssl://' . $this->APNS_HOST . ':' . self::APNS_PORT, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

        //info
        $this->payload['aps'] = array(
            'alert' => $message,
            'badge' => 0,
            'sound' => 'default'
        );
        if( $urlScheme ){
            $this->payload['aps']['link'] = $urlScheme;
        }
        $this->payload = json_encode($this->payload);
    }

    public function send(){
        foreach($this->tokens as $token){
            $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $token)) . chr(0) . chr(strlen($this->payload)) . $this->payload;
            $a = fwrite($this->apns, $apnsMessage);
        }
    }

    public function close(){
        //close socket
        socket_close($this->apns);
        fclose($this->apns);
    }

}