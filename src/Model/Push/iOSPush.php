<?php

namespace Core\Model\Push;

use Core\Utils\Config;

class iOSPush extends iOS {

    private $payload = null;
    private $tokens = null;

    private $hasLog = false;
    private $currentTime = '';
    private $logIDs = array();

    public function __construct( $message, $tokens, $urlScheme = '' ){
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
		$this->open();

		for( $i=0; $i<count($this->tokens); $i++ ){
			$token = $this->tokens[$i];
			$result = $this->sendHTTP2Push($token);

			$logID = $this->log($result, $token);
			if( $logID ){
				$this->logIDs[$i] = $logID;
			}

			$this->checkAppleErrorResponse($result, $token, $i);
		}

		$this->close();
    }

    function sendHTTP2Push($token) {
        curl_setopt_array($this->currentSocket, array(
            CURLOPT_URL => $this->APNS_HOST . '/3/device/' . $token,
            CURLOPT_PORT => $this->APNS_PORT,
            CURLOPT_HTTPHEADER => array(
                'apns-topic:' . $this->appBundle,
                'User-Agent: ' . $this->appName
            ),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $this->payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSLVERSION => 393216,
            CURLOPT_SSLCERT => realpath($this->APNS_CERT),
            CURLOPT_HEADER => 1
        ));

        $result = curl_exec($this->currentSocket);
        $httpcode = curl_getinfo($this->currentSocket, CURLINFO_HTTP_CODE);
        if( $httpcode == 200 ) {
            $result = true;
        }else{
            preg_match('/(.*){(.*?)}/', $result, $match);
            if( count($match) ){
                $error = json_decode($match[0], true);
                if( array_key_exists('reason', $error) ){
                    $result = $error['reason'];
                }
            }
        }

        return $result;
    }

    private function log($result, $token){
        if( $this->hasLog ){
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

    private function checkAppleErrorResponse($response, $token, $i) {
        if( $response !== true ){
            $hasToDeleteDevice = false;
            switch ($response){
                case 'BadDeviceToken':
                case 'DeviceTokenNotForTopic':
                case 'Unregistered':
                    $hasToDeleteDevice = true;
                    break;
            }

            // delete device
            $this->ko++;
            if( $hasToDeleteDevice ){
                $this->deleteDevice($token);
            }

            // update log
            if( $this->hasLog ){
                $sql = '
                    UPDATE appacman_log_ios
                    SET result = :result
                    WHERE id_appacman_log_ios = :id
                ';
                $params = array(
                    'id'        => array('value' => $this->logIDs[$i], 'type' => \PDO::PARAM_INT),
                    'result'    => array('value' => $response,          'type' => \PDO::PARAM_STR),
                );
                $this->mysql->query($sql, $params);
            }
        }
    }

}
