<?php

namespace Core\Controller;

use Core\Utils\Config;
use Core\Utils\Exception;
use Core\Utils\Language;
use Core\View\Extension\Twig;
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

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
     * @var string $rootDomain. Base URL without language (if any)
     */
    protected $rootDomain = '';

    /**
     * @var string $staticDomain. Base URL to static
     */
    protected $staticDomain = '';

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
        $this->rootDomain = $config->getBaseDomain();

        $this->assign('domain', $this->domain);
        $this->assign('rootDomain', $this->rootDomain);
        $this->assign('host', $_SERVER['HTTP_HOST']);
        $this->assign('publicIP', $_SERVER['REMOTE_ADDR']);
        $this->assign('isDev', IS_DEV);
        if (!IS_DEV) {
            $this->assign('min', '.min');
        }

        $staticPath = 'static/';
        $explode = explode('/', $_SERVER['DOCUMENT_ROOT']);
        if( $explode[count($explode)-1] != 'public') $staticPath = 'public/' . $staticPath;
        $this->staticDomain = $this->rootDomain . $staticPath;
        $this->assign('staticDomain', $this->staticDomain);

        //language
        $culture = $config->getLanguage();
        $this->assign('lang', $culture);
        $this->assign('langLocale', Language::getLocale($culture));

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
     * @param int $status       Code (200, ...)
     */
    protected function template($file, $status = 200) {
        $this->view->setInfo($this->info);
        $this->view->template($file, $status);
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
    protected function xml($file, $path = null){
        $this->view->setInfo($this->info);
        $this->view->xml($file, $path);
    }

    /**
     * makes a redirection
     * @param string $url       URL to be redirect
     * @param int $status       Code (301, 302)
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
        //return array('ttl' => 300, 'key' => array('controller', __CLASS__));
        return false;
    }

    /**
     * loads the cache from a model
     * @param \Core\Model\Model $model      object
     * @param string $method                method to be called
     * @param array $params                 cache params same as function params
     * @return mixed|null
     */
    protected function loadCache($model, $method, $params = array()){
        $cacheDefinition = $model->getCacheDef($method, $params);
        $result = $this->modelCache->getCache($cacheDefinition);
        if( $result == '' ){
            $result = call_user_func_array( array($model, $method), $params);
            $this->modelCache->saveCache($result);
        }
        return $result;
    }

    /**
     * obtain html content of twig file
     * @param string $file
     * @param array $info
     * @param null $path
     *
     * @return string
     */
    protected function getHTML($file, $info = array(), $path = null) {
        if( $path == null ){
            $path = DIR_ROOT . 'src/' . $this->view->getProjectFolder() . '/View/';
        }

        $loader = new FilesystemLoader(array($path));
        $twig = new Environment($loader);

        $twig->addExtension( new Translation() );
        $filter = new TwigFilter(
            'trans',
            function ($context, $string) {
                return Translation::transGetText($string, $context);
            },
            ['needs_context' => true]
        );
        $twig->addFilter($filter);

        $twig->addExtension( new Twig() );
        if( array_key_exists('twig_filters', $info) ){
            foreach($info['twig_filters'] as $filter){
                $twig->addFilter($filter);
            }
            unset($info['twig_filters']);
        }

        //response
        $template = $twig->load($file);
        return $template->render($info);
    }

    protected function getCanonicalURL($pagination = array(), $pageKey = 'p'){
        $page = 1;
        $parameters = array();
        $query = $_SERVER['QUERY_STRING'];
        if( !empty($query) ){
            parse_str($query, $parameters);
            if( array_key_exists($pageKey, $parameters) ){
                $page = (int)$parameters[$pageKey];
                unset($parameters[$pageKey]);
                $query = http_build_query($parameters);
            }
        }

        // final URL without page
        $return = array();
        $url = $this->getBaseURL();
        $canonical = $url;
        if(!str_ends_with($canonical, '/')){
            $canonical .= '/';
        }
        if( !empty($query) ){
            $canonical .= '?' . $query;
        }
        $return['canonical'] = $canonical;

        // prev and next URL
        $current = $page;
        $lastPage = $page;
        if( count($pagination) ){
            $current = $pagination['current'];
            if( count($pagination['pages']) ){
                $lastPage = $pagination['pages'][ count($pagination['pages']) - 1 ];
            }
        }
        if( ($page > 0 && $page != $lastPage) || $current < $lastPage ){
            $parameters['p'] = $page + 1;
            $return['next'] = $url . '?' . http_build_query($parameters);
        }
        if( $page > 1 ){
            $parameters['p'] = $page - 1;
            $return['prev'] = $url . '?' . http_build_query($parameters);
        }

        return $return;
    }

    private function getBaseURL(){
        $canonical = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        if( !empty($_SERVER['REDIRECT_URL']) ){
            $canonical .= $_SERVER['REDIRECT_URL'];
        }
        return $canonical;
    }
    
}