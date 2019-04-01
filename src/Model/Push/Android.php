<?php

namespace Core\Model\Push;

use Core\Utils\Config;

class Android extends Base {

    const URL = 'https://fcm.googleapis.com/fcm/send';

    private $API_KEY = null;
    private $APP_NAME = null;

    private $tokens = array();
    private $headers = array();
    private $fields = array();
    private $ch = null;

    public function __construct( $message, $tokens, $urlScheme = ''){
        parent::__construct();

        $config = Config::getInstance();
        $pushConfig = $config->get('push');
        $this->tokens = $tokens;
        $this->total = count($this->tokens);
        $this->API_KEY = $pushConfig['android_key'];
        $this->APP_NAME = $pushConfig['android_app_name'];

        $this->headers = array(
            'Authorization: key=' . $this->API_KEY,
            'Content-Type: application/json'
        );
        $this->fields = array(
            'data'              => array( 'title' => $this->APP_NAME, 'message' => $message )
        );
        if( $urlScheme ){
            $this->fields['data']['link'] = $urlScheme;
        }
    }

    public function send(){
        $this->tokens = array_chunk($this->tokens, 300);

        foreach($this->tokens as $registrationIDs){
            // set tokens
            $this->fields['registration_ids'] = $registrationIDs;

            // Open connection
            $this->ch = curl_init();
            curl_setopt( $this->ch, CURLOPT_URL, self::URL);
            curl_setopt( $this->ch, CURLOPT_POST, true );
            curl_setopt( $this->ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $this->ch, CURLOPT_POSTFIELDS, json_encode( $this->fields ) );

            // Execute post
            $result = curl_exec($this->ch);
            $result = json_decode($result, true);
            if( $result == null || $result == 'null' ){
                $this->error = curl_error($this->ch);
                $this->log(array('curl_error' => $this->error));
            }else{
                $this->ok += $result['success'];

                for($i=0; $i<count($result['results']); $i++){
                    if( array_key_exists('error', $result['results'][$i]) ){
                        switch ($result['results'][$i]['error']){
                            case 'NotRegistered':
                                $this->deleteDevice($this->tokens[$i]);
                                $this->total -= 1;
                                break;
                            case 'InvalidParameters':
                                $this->tokens[$i] = str_replace('"', '', $this->tokens[$i]);
                                if( $this->tokens[$i] == 'BLACKLISTED' ){
                                    $this->deleteDevice($this->tokens[$i]);
                                    $this->total -= 1;
                                }
                                break;
                        }

                    }
                }

                $this->log($result, $registrationIDs);
            }

            //close socket
            curl_close($this->ch);
        }
    }

    public function close(){
        // nothing
    }

    private function log($result, $tokens){
        if( $this->mysql->tableExists('appacman_log_android') ){
            $tokens = array_map(function($n){ return '"' . $n . '"'; }, $tokens);
            $tokens = implode(',', $tokens);
            $sql = '
                SELECT GROUP_CONCAT(id_user) AS users
                FROM appacman_push_device
                WHERE token IN('.$tokens.')
            ';
            $users = $this->mysql->query($sql);

            $data = json_encode($this->fields['data'], JSON_UNESCAPED_SLASHES);
            $result = json_encode($result, JSON_UNESCAPED_SLASHES);
            $sql = '
                INSERT INTO appacman_log_android
                SET tokens = :tokens, data = :data, result = :result
            ';
            $params = array(
                'tokens'    => array('value' => $tokens,	'type' => \PDO::PARAM_STR),
                'data'      => array('value' => $data,     	'type' => \PDO::PARAM_STR),
                'result'    => array('value' => $result,	'type' => \PDO::PARAM_STR),
            );
            if( count($users) ){
                $sql .= ', users = :users';
                $params['users'] = array('value' => $users[0]['users'], 'type' => \PDO::PARAM_STR);
            }

            $this->mysql->query($sql, $params);
        }
    }

    public function addQuotes($element){
        return '"' . $element . '"';
    }

}