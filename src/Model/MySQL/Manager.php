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
     * @param string $name      connection name
     * @param array $dbConfig   database connection properties
     * @return \Core\Model\MySQL\PDO
     */
    public static function getInstance($name = 'default', $dbConfig = null){
        if( !array_key_exists($name, self::$instance) ){
            self::$instance[$name] = new PDO($dbConfig);
        }
        return self::$instance[$name];
    }

}