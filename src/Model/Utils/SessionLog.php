<?php

namespace Core\Model\Utils;

use Core\Model\MySQL\Manager;

class SessionLog {

    /**
     * @var  \Core\Model\MySQL\Manager  Database connection
     */
    public $mysql = null;

    public function __construct(){
        $this->mysql = Manager::getInstance();
    }

    public function getID(){
        return session_id();
    }

    public function logOut($userID){
        $sql = '
            DELETE FROM appacman_user_session
            WHERE id_appacman_user = :id
        ';
        $params = array(
            'id' => array('value' => $userID, 'type' => \PDO::PARAM_INT)
        );
        $this->mysql->query($sql, $params);
    }

    public function saveID($userID){
        if( !$this->sessionExists($userID) ){
            $sql = '
                INSERT INTO appacman_user_session
                SET id_appacman_user = :id, session = :session
            ';
            $params = array(
                'id'        => array('value' => $userID,            'type' => \PDO::PARAM_INT),
                'session'   => array('value' => $this->getID(),     'type' => \PDO::PARAM_STR)
            );
            $this->mysql->query($sql, $params);
        }
    }

    public function removeID($userID){
        if( $this->sessionExists($userID) ){
            $sql = '
                DELETE FROM appacman_user_session
                WHERE id_appacman_user = :id AND session = :session
            ';
            $params = array(
                'id'        => array('value' => $userID,            'type' => \PDO::PARAM_INT),
                'session'   => array('value' => $this->getID(),     'type' => \PDO::PARAM_STR)
            );
            $this->mysql->query($sql, $params);
        }
    }

    public function sessionExists($userID){
        $sql = '
            SELECT *
            FROM appacman_user_session
            WHERE id_appacman_user = :id AND session = :session
        ';
        $params = array(
            'id'        => array('value' => $userID,            'type' => \PDO::PARAM_INT),
            'session'   => array('value' => $this->getID(),     'type' => \PDO::PARAM_STR)
        );
        $session = $this->mysql->query($sql, $params);

        if( count($session) ){
            return true;
        }
        return false;
    }

}