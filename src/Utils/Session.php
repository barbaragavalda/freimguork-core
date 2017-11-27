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

    const DURATION = 86400;  // 1 day

    /**
     * @var \Core\Utils\Session $instance. Instance of the singleton
     */
    private static $instance;

    /**
     * @var string $domain. The domain that the cookie is available to
     */
    private $domain = '';

    /**
     * load session
     * @param $id
     */
    private function __construct($id = null){
        if( $id != null ){
            $this->domain = $id;
        }
    }

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @param $id
     * @return \Core\Utils\Session
     */
    public static function getInstance($id = null){
        if( self::$instance === null) {
            self::$instance = new Session($id);
        }
        return self::$instance;
    }

    /**
     * get value of a cookie
     * @param $key
     * @return mixed|null
     */
    public function get($key){
        if( isset($_COOKIE[$key]) ){
            return json_decode($_COOKIE[$key], true);
        }else{
            return null;
        }
    }

    /**
     * set cookie
     * @param $key
     * @param $value
     */
    public function set($key, $value){
        $this->save($key, $value);
    }

    /**
     * delete cookie
     * @param $key
     */
    public function delete($key){
        if( isset($_COOKIE[$key]) ){
            $this->save($key, '', -self::DURATION);
            unset($_COOKIE[$key]);
        }
    }

    /**
     * delete all cookies
     */
    public function clear(){
        foreach( $_COOKIE as $key => $value ){
            $this->delete($key);
        }
    }

    /**
     * save cookie
     * @param $key
     * @param $value
     * @param int $expiration
     */
    private function save($key, $value, $expiration = self::DURATION){
        $value = json_encode($value);
        setcookie($key, $value, time()+$expiration, '/', $this->domain);
        $_COOKIE[$key] = $value;
    }

}