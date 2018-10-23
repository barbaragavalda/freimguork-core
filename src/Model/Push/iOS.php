<?php

namespace Core\Model\Push;

use Core\Utils\Config;

abstract class iOS extends Base {

    protected $APNS_PORT = 2195;
    protected $APNS_HOST = null;
    protected $APNS_CERT = null;
    protected $APNS_PASSWORD = null;

    protected $apns = false;
    protected $currentSocket = 0;

    public function __construct($host, $port = 2195){
        parent::__construct();

        $config = Config::getInstance();
        $pushConfig = $config->get('push');
        $this->APNS_PORT = $port;
        $this->APNS_HOST = $host;
        $this->APNS_CERT = $pushConfig['ios_cert'];
        $this->APNS_PASSWORD = $pushConfig['ios_password'];

        $this->open();
    }

    protected function open(){
        //open socket
        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $this->APNS_CERT);
        stream_context_set_option($streamContext, 'ssl', 'passphrase', $this->APNS_PASSWORD);
        $this->apns[$this->currentSocket] = stream_socket_client('ssl://' . $this->APNS_HOST . ':' . $this->APNS_PORT, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

        //This allows fread() to return right away when there are no errors. But it can also miss errors during last seconds of sending, as there is a delay before error is returned.
        stream_set_blocking($this->apns[$this->currentSocket], 0);
    }

    public function close(){
        //close socket
        socket_close($this->apns[$this->currentSocket]);
        fclose($this->apns[$this->currentSocket]);
        $this->apns[$this->currentSocket] = false;
    }

}
