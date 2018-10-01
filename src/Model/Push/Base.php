<?php

namespace Core\Model\Push;

use Core\Model\Model;

abstract class Base extends Model {

    protected $result = array();

    protected $total = 0;
    protected $ok = 0;
    protected $error = '';

    public function getResult(){
        return $this->result;
    }

    public function deleteDevice($token){
        $token = str_replace('"', '', $token);
        $sql = '
            DELETE FROM appacman_push_device
            WHERE token = :token
        ';
        $params = array(
            'token' => array('value' => $token, 'type' => \PDO::PARAM_STR)
        );
        $this->mysql->query($sql, $params);
    }

    public function getEmailResult(){
        $ko = $this->total - intval($this->ok);

        $result = ' enviada a ' . $this->ok .' dispositivos';
        if( $ko > 0 ){
            $result .= ' <span style="color:red">(fallido en ' . $ko . ' dispositivos)</span>';
            if( $this->error != '' ) $result .= ' <div style="color:red"><b>ERROR RESPUESTA: </b>' . $this->error . '</div>';
        }else $result .= ' (ninguno fallido)';
        return $result;
    }

    abstract  public function send();

    abstract public function close();

} 