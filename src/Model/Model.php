<?php

namespace Core\Model;

/**
 * Class Model
 *
 * MySQL connection
 *
 * @package Core\Utils
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
class Model {

    /**
     * @var null. Database connection
     */
    private $mysql = null;

    public function __construct(){
        $this->mysql = MySQL::getInstance();
    }

    /* TODO
    public function getCacheDef($method, array $params)
    {
        return false;
    }
    */

}