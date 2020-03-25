<?php

namespace Core\Model\Push;

use Core\Utils\Config;

class iOSPush extends iOS {

    private $payload = null;
    private $tokens = null;

    private $hasLog = false;
    private $currentTime = '';
    private $logIDs = array();
    private $currentToken = 0;

    public function __construct( $message, $tokens, $urlScheme = '', $doLog = true){
        $this->doLog = $doLog;

        $config = Config::getInstance();
        $pushConfig = $config->get('push');
        $host = $pushConfig['ios_host'];

        parent::__construct( $host );

        $this->hasLog = $this->mysql->tableExists('appacman_log_ios');
        $this->currentTime = date('YmdHis');

        $this->checkTokens($tokens);
        $this->preparePayload($message, $urlScheme);
    }

    private function checkTokens($tokens){
        // check token format
        foreach($tokens as $token){
            if( ctype_xdigit($token) ){
                $this->tokens[] = $token;
            }else{
                $this->deleteDevice($token);
            }
        }
        $this->total = count($this->tokens);
    }

    private function preparePayload($message, $urlScheme){
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
        $apple_expiry = time() + 1; //(60 * 60); //Keep push alive (waiting for delivery) for 1 hour

        //foreach($this->tokens as $id => $token){
        while($this->currentToken < count($this->tokens)){
            if( $this->apns[$this->currentSocket] === false ){
                // connetion closed
            }else{
                //Enhanced Notification
                $id = $this->currentToken;
                $token = $this->tokens[$id];
                $apnsMessage = pack("C", 1) . pack("N", $id.'_'.$this->currentTime) . pack("N", $apple_expiry) . pack("n", 32) . pack('H*', str_replace(' ', '', $token)) . pack("n", strlen($this->payload)) . $this->payload;

                //SEND PUSH we assume it is correct by default
                $result = fwrite($this->apns[$this->currentSocket], $apnsMessage);

                $logID = $this->log($result, $token);
                if( $logID ){
                    $this->logIDs[$id] = $logID;
                }

                //We can check if an error has been returned while we are sending
                $this->currentToken++;
                $this->checkAppleErrorResponse();
            }
        }

        // But it can also miss errors during last seconds of sending, as there is a delay before error is returned. Workaround is to pause briefly AFTER sending last notification, and then do one more fread() to see if anything else is there.
        usleep(500000); // Pause for half a second.
        $this->checkAppleErrorResponse();
    }

    private function log($result, $token){
        if( $this->hasLog && $this->doLog ){
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
                'token'        => array('value' => $token,     'type' => \PDO::PARAM_STR),
                'id_user'   => array('value' => $userID,    'type' => \PDO::PARAM_STR),
                'data'      => array('value' => $data,        'type' => \PDO::PARAM_STR),
                'result'    => array('value' => $result,    'type' => \PDO::PARAM_STR),
            );
            $this->mysql->query($sql, $params);
            return $this->mysql->lastInsertId();
        }
        return false;
    }

    private function checkAppleErrorResponse() {
        //byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID). Should return nothing if OK.
        $apple_error_response = fread($this->apns[$this->currentSocket], 6);

        if( $apple_error_response ){
            $this->close();
            $this->open();

            //unpack the error response (first byte 'command" should always be 8)
            $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);
            $response = '';

            $hasToDeleteDevice = false;
            if ($error_response['status_code'] == '0') {
                $response = 'No errors encountered';
            } else if ($error_response['status_code'] == '1') {
                $response = 'Processing error';
            } else if ($error_response['status_code'] == '2') {
                $response = 'Missing device token';
                $hasToDeleteDevice = true;
            } else if ($error_response['status_code'] == '3') {
                $response = 'Missing topic';
            } else if ($error_response['status_code'] == '4') {
                $response = 'Missing payload';
            } else if ($error_response['status_code'] == '5') {
                $response = 'Invalid token size';
                $hasToDeleteDevice = true;
            } else if ($error_response['status_code'] == '6') {
                $response = 'Invalid topic size';
            } else if ($error_response['status_code'] == '7') {
                $response = 'Invalid payload size';
            } else if ($error_response['status_code'] == '8') {
                $response = 'Invalid token';
                $hasToDeleteDevice = true;
            } else if ($error_response['status_code'] == '255') {
                $response = 'None (unknown)';
            } else {
                $response = $error_response['status_code'] . ' - Not listed';
            }

            $id = str_replace('_'.$this->currentTime, '', $error_response['identifier']);

            // delete device
            $this->ko++;
            if( $hasToDeleteDevice ){
                $this->deleteDevice($this->tokens[$id]);
            }

            // update log
            if( $this->hasLog ){
                $sql = '
                    UPDATE appacman_log_ios
                    SET result = :result
                    WHERE id_appacman_log_ios = :id
                ';
                $params = array(
                    'id'        => array('value' => $this->logIDs[$id], 'type' => \PDO::PARAM_INT),
                    'result'    => array('value' => $response,          'type' => \PDO::PARAM_STR),
                );
                $this->mysql->query($sql, $params);
            }

            // start again
            $this->currentToken = $id + 1;
            $this->send();
        }
    }

}
