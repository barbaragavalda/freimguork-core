<?php

namespace Core\Model;

use Core\Model\MySQL\Manager;
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
     * @var  \Core\Model\MySQL\Manager  Database connection
     */
    public $mysql = null;

    /**
     * @var int  current lang id
     */
    protected $langID = 0;

    /**
     * @var int     identifier
     */
    protected $id = 0;

    /**
     * @var string  encrypting key
     */
    protected $key = '';

    /**
     * @var null    creation timestamp
     */
    protected $created = null;

    public function __construct(){
        $this->mysql = Manager::getInstance();

        $session = Session::getInstance();
        $this->langID = $session->get('lang_id');
    }

    public function getID(){
        return $this->id;
    }

    public function setID($id){
        $this->id = $id;
    }

    protected function setKey(){
        $this->key = $this->id . '_' . $this->created . '_';
    }

    public function getCreated(){
        return $this->created;
    }

    public function setCreated($created){
        $this->created = $created;
    }

    protected function getFile($fileID, $suffix = ''){
        $file = new File($fileID);
        return $file->getAbsolutePath($suffix);
    }

    protected function getFBImage($fileID, $suffix = ''){
        $file = new File($fileID);
        $size = $file->getSize($suffix);
        if( $size !== false ){
            return array(
                'image' => $file->getAbsolutePath($suffix),
                'width' => $size['width'],
                'height' => $size['height']
            );
        }
        return null;
    }

    protected function uploadFile($postFile){
        $file = null;
        if( !empty($postFile) ){
            $file = new File();
            $file->save($postFile);
        }
        return $file;
    }

    public function getCacheDef($method, array $params) {
        return false;
        // or return array('ttl' => 300, 'key' => $params);
    }

    /**
     * Magic method that tries to call the function in the MySQL class instead of this (SQL Manager) class.
     * @param string $method            name of the function.
     * @param array $args               with the parameters passed to the function
     * @return mixed                    return of the called function
     */
    public function __call($method, $args){
        if( method_exists($this->mysql, $method) ){
            return call_user_func_array(array($this->mysql, $method), $args);
        }
    }

}