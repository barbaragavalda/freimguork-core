<?php

namespace Core\Utils;


/**
 * Class Session
 *
 * Current language
 *
 * @package Core\Utils
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class Session{

    /**
     * @var Core\Utils\Session $instance.  Instance of the singleton
     */
    private static $instance;

    private $id = '';

    /**
     * load session
     * @param $id
     */
    private function __construct($id = null){
        if( $id != null ){
            $this->id = $id;
            if( !array_key_exists($this->id, $_SESSION) ){
                $_SESSION[$this->id] = array();
            }
        }
    }

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @param $id
     * @return Core\Utils\Session
     */
    public static function getInstance($id = null){
        if( self::$instance === null) {
            self::$instance = new Session($id);
        }
        return self::$instance;
    }

    public function get($key){
        if( array_key_exists($key, $_SESSION[$this->id]) ){
            return $_SESSION[$this->id][$key];
        }else{
            return null;
        }
    }

    public function set($key, $value){
        $_SESSION[$this->id][$key] = $value;
    }

    public function delete($key){
        if( array_key_exists($key, $_SESSION[$this->id]) ){
            unset( $_SESSION[$this->id][$key] );
        }
    }

    public function clear(){
        unset( $_SESSION[$this->id] );
    }

}