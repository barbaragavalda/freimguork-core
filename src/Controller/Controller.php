<?php

namespace Core\Controller;

use Core\Session;
use Core\Utils\Config;
use Core\Utils\Exception;

/**
 * Class Controller
 *
 * All Controllers must extend from this controller
 *
 * @package Core\Controller
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
abstract class Controller {

    /**
     * @var string $domain. Base URL including language (if any)
     */
    protected $domain = '';

    /**
     * @var int $langCode. Current language code
     */
    protected $langCode = '';

    /**
     * @var array $params. Parameters from URL petition
     */
    protected $params = array();

    /**
     * @var \Core\View $view. View class
     */
    private $view = array();

    /**
     * @var array $info. Content of the variables that the view needs
     */
    private $info = array();

    //TODO protected $cache_manager = null;

    /**
     *
     */
    public function __construct() {
        //TODO $this->cache_manager = new \Core\CacheManager();

        $config = Config::getInstance();
        $this->domain = $config->getDomain();
        $this->assign('domain', $this->domain);
        $this->assign('static_domain', $config->getStaticDomain());

        $this->assign('lang', $this->langCode);
    }

    /***
     * function to be executed by Bootstrap
     */
    abstract public function build();

    /***
     * sets the params from the URL
     * @param array $params
     */
    public function setParams($params) {
        $this->params = $params;
    }

    /***
     * sets the object view
     * @param \Core\View $view.
     */
    public function setView($view) {
        $this->view = $view;
    }

    /***
     * saves info that the view needs
     * @param $var_name     key
     * @param $value        value
     */
    public function assign( $var_name, $value ){
        $this->info[$var_name] = $value;
    }

    /**
     * returns the final result from the view
     */
    public function getResponse(){
        return $this->view->getResponse();
    }

    /**
     * renders a json
     */
    protected function json(){
        $this->view->setInfo($this->info);
        $this->view->json();
    }

    /**
     * makes a redirection
     * @param string $url . URL to be redirect
     * @param int $status . Code (301, 302)
     * @throws exception
     */
    protected function redirect($url, $status = 301){
        $this->view->setInfo($this->info);
        try{
            $this->view->redirect($url, $status);
        } catch (Exception $e) {
            $e->showException();
        }
    }

    /**
     * renders a template file (twig)
     * @param string $file . Name of the template file
     */
    protected function template($file) {
        $this->view->setInfo($this->info);
        $this->view->template($file);
    }

    /* TODO
    public function getCacheDef()
    {
        return array('ttl' => 300, 'key' => array('controller', __CLASS__));
    }
//    */
//
//    protected function getParam( $param )
//    {
//        if( array_key_exists($param, $this->params['params']) )
//            return $this->params['params'][$param];
//        else
//            return false;
//    }
//
//    protected function loadCache($params_cache, $model, $function, $function_params = array()){
//        $cache_def = $model->getCacheDef($function, $params_cache);
//        $result = $this->cache_manager->getCache($cache_def);
//
//        if( $result == '' ){
//            $result = call_user_func_array( array($model, $function), $function_params);
//            $this->cache_manager->setCache($result);
//        }
//        return $result;
//    }
}