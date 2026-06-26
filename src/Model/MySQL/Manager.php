<?php

namespace Core\Model\MySQL;

/**
 * Class Manager
 * MySQL connection manager
 */
class Manager
{

    /**
     * @var array of \Core\Model\MySQL\PDO singletons
     */
    private static array $instance = array();

    /**
     * initializes the instance (if needed) based on the singleton pattern
     *
     * @param string $name       connection name
     * @param ?array $dbConfig   database connection properties
     * @param bool   $throwError throw error or just ignore it
     *
     * @return PDO
     * @throws \Core\Utils\Exception
     */
    public static function getInstance(string $name = 'default', ?array $dbConfig = null, bool $throwError = true): PDO
    {
        if (!array_key_exists($name, self::$instance)) {
            self::$instance[ $name ] = new PDO($dbConfig, $throwError);
        }
        return self::$instance[ $name ];
    }

}