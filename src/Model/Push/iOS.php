<?php

namespace Core\Model\Push;

use Core\Utils\Config;

class iOS extends Base {

    const APNS_PORT = 2195;

    private $APNS_HOST = null;
    private $APNS_CERT = null;
    private $APNS_PASSWORD = null;

    private $payload = null;
    private $tokens = null;

    public function __construct( $message, $tokens, $urlScheme = '' ){
        parent::__construct();

        $this->tokens = $tokens;
        $this->total = count($this->tokens);

        $config = Config::getInstance();
        $pushConfig = $config->get('push');
        $this->APNS_HOST = $pushConfig['ios_host'];
        $this->APNS_CERT = $pushConfig['ios_cert'];
        $this->APNS_PASSWORD = $pushConfig['ios_password'];

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
            $streamContext = stream_context_create();
            stream_context_set_option($streamContext, 'ssl', 'local_cert', $this->APNS_CERT);
            stream_context_set_option($streamContext, 'ssl', 'passphrase', $this->APNS_PASSWORD);
            $apns = stream_socket_client('ssl://' . $this->APNS_HOST . ':' . self::APNS_PORT, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

            $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $token)) . chr(0) . chr(strlen($this->payload)) . $this->payload;
            $result = fwrite($apns, $apnsMessage);
            if( $result > 0 ) $this->ok += 1;

            $this->log($result, $token);

            socket_close($apns);
            fclose($apns);
        }
    }

    public function close(){
        // nothing
    }

    private function log($result, $token){
        if( $this->mysql->tableExists('appacman_log_ios') ){
            $sql = '
                SELECT id_user
                FROM appacman_push_device
                WHERE token = :token
            ';
            $params = array(
                'token' => array('value' => $token, 'type' => \PDO::PARAM_STR)
            );
            $users = $this->mysql->query($sql, $params);
            $userID = '';
            if( count($users) ){
                $userID = $users[0]['id_user'];
            }

            $data = json_encode($this->payload, JSON_UNESCAPED_SLASHES);
            $data = str_replace('\"', "'", $data);
            $data = str_replace('"', '', $data);
            $data = str_replace('\\\/', '/', $data);
            $sql = '
                INSERT INTO appacman_log_ios
                SET token = :token, id_user = :id_user, data = :data, result = :result
            ';
            $params = array(
                'token'    	=> array('value' => $token,     'type' => \PDO::PARAM_STR),
                'id_user'   => array('value' => $userID,    'type' => \PDO::PARAM_STR),
                'data'      => array('value' => $data,		'type' => \PDO::PARAM_STR),
                'result'    => array('value' => $result,    'type' => \PDO::PARAM_STR),
            );
            $this->mysql->query($sql, $params);
        }
    }

}