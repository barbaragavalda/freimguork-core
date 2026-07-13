<?php

namespace Core\Model\Utils;

use Core\Model\MySQL\Manager;
use Core\Model\MySQL\PDO;

class SessionLog
{

    public PDO $mysql;

    /**
     * @throws \Core\Utils\Exception
     */
    public function __construct()
    {
        $this->mysql = Manager::getInstance();
    }

    public function getID(): false|string
    {
        return session_id();
    }

    public function logOut($userID): void
    {
        $sql      = '
            SELECT *
            FROM appacman_user_session
            WHERE id_appacman_user = :id
        ';
        $params   = array(
            'id' => array('value' => $userID, 'type' => \PDO::PARAM_INT)
        );
        $sessions = $this->mysql->query($sql, $params);

        if (count($sessions)) {
            $currentID = $this->getID();
            session_commit();
            foreach ($sessions as $session) {
                session_id($session['session']);
                session_start();
                session_destroy();
                session_commit();
            }

            session_id($currentID);
            session_start();
            session_commit();

            $sql = '
                DELETE FROM appacman_user_session
                WHERE id_appacman_user = :id
            ';
            $this->mysql->query($sql, $params);
        }
    }

    public function saveID($userID): void
    {
        if (!$this->sessionExists($userID)) {
            $sql    = '
                INSERT INTO appacman_user_session
                SET id_appacman_user = :id, session = :session
            ';
            $params = array(
                'id'      => array('value' => $userID, 'type' => \PDO::PARAM_INT),
                'session' => array('value' => $this->getID(), 'type' => \PDO::PARAM_STR)
            );
            $this->mysql->query($sql, $params);
        }
    }

    public function removeID($userID): void
    {
        if ($this->sessionExists($userID)) {
            $sql    = '
                DELETE FROM appacman_user_session
                WHERE id_appacman_user = :id AND session = :session
            ';
            $params = array(
                'id'      => array('value' => $userID, 'type' => \PDO::PARAM_INT),
                'session' => array('value' => $this->getID(), 'type' => \PDO::PARAM_STR)
            );
            $this->mysql->query($sql, $params);
        }
    }

    public function sessionExists($userID): bool
    {
        $sql     = '
            SELECT *
            FROM appacman_user_session
            WHERE id_appacman_user = :id AND session = :session
        ';
        $params  = array(
            'id'      => array('value' => $userID, 'type' => \PDO::PARAM_INT),
            'session' => array('value' => $this->getID(), 'type' => \PDO::PARAM_STR)
        );
        $session = $this->mysql->query($sql, $params);

        return count($session) > 0;
    }

}