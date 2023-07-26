<?php

namespace Core\Model\Push;

use Core\Utils\Config;
use Google\Client;

class Android extends Base
{

    private $API_URL = null;

    private $tokens     = array();
    private $message    = array();
    private $httpClient = null;

    public function __construct($message, $tokens, $urlScheme = '', $image = '', $doLog = true)
    {
        $this->doLog = $doLog;

        parent::__construct();

        $config     = Config::getInstance();
        $pushConfig = $config->get('push');

        $this->API_URL = sprintf($pushConfig['android_host'], $pushConfig['android_project']);

        $this->tokens = $tokens;
        $this->total  = count($this->tokens);

        $client = new Client();
        $client->setDeveloperKey($pushConfig['android_key']);
        $client->setAuthConfig($pushConfig['android_cert']);
        $client->addScope($pushConfig['android_scope']);
        $this->httpClient = $client->authorize();

        $this->message = [
            'message' => [
                'notification' => [
                    'body'  => $message,
                    'title' => $pushConfig['android_app_name']
                ]
            ]
        ];

        if ($urlScheme) {
            $this->message['message']['data']['link'] = $urlScheme;
        }
    }

    public function send()
    {
        $this->tokens = array_chunk($this->tokens, 300);
        echo '<pre>' . print_r($this->tokens, true) . '</pre>';

        foreach ($this->tokens as $tokens) {
            foreach ($tokens as $token) {
                $this->message['message']['token'] = $token;

                $response = $this->httpClient->post($this->API_URL, ['json' => $this->message]);
                echo '<pre>' . print_r($response, true) . '</pre>';
                if ($response->getStatusCode() == 200) {
                    $this->ok += $result['success'];
                } else {
                    if (in_array($response->getStatusCode(), array(400, 404))) {
                        $this->deleteDevice($token);
                    }

                    $this->error = $response->getReasonPhrase();
                    $this->log($this->error, array($token));
                }
            }
        }
    }

    public function close()
    {
        // nothing
    }

    private function log($result, $tokens)
    {
        if ($this->mysql->tableExists('appacman_log_android') && $this->doLog) {
            $tokens = array_map(
                function ($n) {
                    return '"' . $n . '"';
                },
                $tokens
            );
            $tokens = implode(',', $tokens);
            $sql    = '
                SELECT GROUP_CONCAT(id_user) AS users
                FROM appacman_push_device
                WHERE token IN(' . $tokens . ')
            ';
            $users  = $this->mysql->query($sql);

            $data   = json_encode($this->fields['data'], JSON_UNESCAPED_SLASHES);
            $result = json_encode($result, JSON_UNESCAPED_SLASHES);
            $sql    = '
                INSERT INTO appacman_log_android
                SET tokens = :tokens, data = :data, result = :result
            ';
            $params = array(
                'tokens' => array('value' => $tokens, 'type' => \PDO::PARAM_STR),
                'data'   => array('value' => $data, 'type' => \PDO::PARAM_STR),
                'result' => array('value' => $result, 'type' => \PDO::PARAM_STR),
            );
            if (count($users)) {
                $sql             .= ', users = :users';
                $params['users'] = array('value' => $users[0]['users'], 'type' => \PDO::PARAM_STR);
            }

            $this->mysql->query($sql, $params);
        }
    }

    public function addQuotes($element)
    {
        return '"' . $element . '"';
    }

}