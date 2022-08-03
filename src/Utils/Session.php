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
     * @var string $path. The path that the cookie is available to
     */
    private $path = '/';

    /**
     * @var string $domain. The domain that the cookie is available to
     */
    private $domain = '';

    /**
     * load session
     * @param string $domain
     */
    private function __construct($domain = null){
        if( $domain != null ){
            $this->path = '/';
            $this->domain = $domain;
        }
    }

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @param string $id
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
     * @param string $key
     * @return mixed|null
     */
    public function get($key){
        if( isset($_COOKIE[$key]) ){
            return json_decode($_COOKIE[$key], true);
        }
        return null;
    }

    /**
     * set cookie
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value){
        $this->save($key, $value);
    }

    /**
     * delete cookie
     * @param string $key
     */
    public function delete($key){
        if( isset($_COOKIE[$key]) ){
            unset($_COOKIE[$key]);
            setcookie($key, '', time() - 3600, '/');
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
     * @param string $key
     * @param mixed $value
     */
    private function save($key, $value){
        $value = json_encode($value);
        $_COOKIE[$key] = $value;
        setcookie($key, $value, time() + self::DURATION, $this->path, $this->domain);
    }

}