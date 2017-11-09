<?php

namespace Core\Model;
use Core\Utils\Session;

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
     * @var null Core|Model|MySQL. Database connection
     */
    protected $mysql = null;

    protected $langID = 0;

    public function __construct(){
        $this->mysql = MySQL::getInstance();

        $session = Session::getInstance();
        $this->langID = $session->get('lang_id');
    }

    public function getCacheDef(array $params) {
        return false;
        // or return array('ttl' => 300, 'key' => $params);
    }

}