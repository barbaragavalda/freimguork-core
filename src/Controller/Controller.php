<?php

namespace Core\Controller;

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
     * @var string $static_domain. Base URL without language (if any)
     */
    protected $static_domain = '';

    /**
     * @var array $params. Parameters from URL petition
     */
    protected $params = array();

    /**
     * @var array $parts. Parts of URL petition
     */
    protected $parts = array();

    /**
     * @var \Core\View\View $view. View class
     */
    private $view = array();

    /**
     * @var array $info. Content of the variables that the view needs
     */
    protected $info = array();

    /**
     * @var \Core\Controller\CacheManager $modelCache. Cache Manager
     */
    protected $modelCache = null;

    /**
     * Bootstrap initialization
     */
    public function __construct() {
        //domains
        $config = Config::getInstance();
        $this->domain = $config->getDomain();
        $this->static_domain = $config->getBaseDomain();
        
        $this->assign('domain', $this->domain);
        $this->assign('static_domain', $this->static_domain . 'public/static/');

        //language
        $this->assign('lang', $config->getLanguage());

        //cache
        $this->modelCache = new CacheManager();
    }

    /***
     * function to be executed by Bootstrap
     */
    abstract public function build();

    /*------------------------------------------
     * VIEW FUNCTIONS
     -------------------------------------------*/
    /***
     * sets the params from the URL (variable parts)
     * @param array $params
     */
    public function setParams($params) {
        $this->params = $params;
    }

    /***
     * sets the parts from the URL (constant parts)
     * @param array $parts
     */
    public function setParts($parts) {
        $this->parts = $parts;
    }

    /***
     * sets the object view
     * @param \Core\View\View $view.
     */
    public function setView($view) {
        $this->view = $view;
    }

    /***
     * saves info that the view needs
     * @param string $var_name
     * @param mixed $value
     */
    public function assign( $var_name, $value ){
        $this->info[$var_name] = $value;
    }

    /**
     * reset info array
     */
    protected function removeInfo(){
        $this->info = array();
    }

    /**
     * returns the final result from the view
     */
    public function getResponse(){
        return $this->view->getResponse();
    }

    /**
     * renders a template file (twig) to html format
     * @param string $file      Name of the template file
     */
    protected function template($file) {
        $this->view->setInfo($this->info);
        $this->view->template($file);
    }

    /**
     * renders a json
     */
    protected function json(){
        $this->view->setInfo($this->info);
        $this->view->json();
    }

    /**
     * renders a template file (twig) to xml format
     * @param string $file      Name of the template file
     */
    protected function xml($file){
        $this->view->setInfo($this->info);
        $this->view->xml($file);
    }

    /**
     * makes a redirection
     * @param string $url       URL to be redirect
     * @param int $status       Code (301, 302)
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

    protected function export($tableName){
        $this->view->setInfo($this->info);
        $this->view->export($tableName);
    }

    /**
     * returns the value of a parameter on the URL
     * @param string $param
     * @return bool|string
     */
    protected function getParam( $param ){
        if( array_key_exists($param, $this->params) )
            return $this->params[$param];
        else
            return false;
    }

    /*------------------------------------------
     * CACHE FUNCTIONS
     -------------------------------------------*/
    /**
     * controller cache definition
     * @return array|bool
     */
    public function getCacheDef() {
        return array('ttl' => 300, 'key' => array('controller', __CLASS__));
    }

    /**
     * loads the cache from a model
     * @param array $params_cache
     * @param \Core\Model\Model $model
     * @param string $function
     * @param array $function_params
     * @return mixed|null
     */
    protected function loadCache($params_cache, $model, $function, $function_params = array()){
        $cache_def = $model->getCacheDef($params_cache);
        $result = $this->modelCache->getCache($cache_def);

        if( $result == '' ){
            $result = call_user_func_array( array($model, $function), $function_params);
            $this->modelCache->saveCache($result);
        }
        return $result;
    }
}