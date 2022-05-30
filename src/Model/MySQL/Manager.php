<?php

namespace Core\Model\MySQL;

/**
 * Class Manager
 *
 * MySQL connection manager
 *
 * @package Core\Model\MySQL
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 04/01/2020
 */
class Manager {

    /**
     * @var array of \Core\Model\MySQL\PDO singletons
     */
    private static $instance = array();

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @param string $name          connection name
     * @param array $dbConfig       database connection properties
     * @param boolean $throwError   throw error or just ignore it
     * @return \Core\Model\MySQL\PDO
     */
    public static function getInstance($name = 'default', $dbConfig = null, $throwError = true){
        if( !array_key_exists($name, self::$instance) ){
            self::$instance[$name] = new PDO($dbConfig, $throwError);
        }
        return self::$instance[$name];
    }

}