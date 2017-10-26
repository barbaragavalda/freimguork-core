<?php

namespace Core;

use Core\Routing\Projects;
use Core\Routing\Router;
use Core\Utils\Config;
use Core\Utils\Language;
use Core\Utils\Session;

/**
 * Class Bootstrap
 *
 * Main class of the framework.
 *
 * @package Core
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class Bootstrap {

    /**
     * @var Router $router. Class that makes the relation between URL and the routing config file
     */
    private $router = null;

    /**
     * @var object $controller. Controller to be executed
     */
    private $controller = null;

    //TODO private $cache_manager = null;

    /**
     * Bootstrap constructor.
     * @param $isDev    indicates if the environment is development or production
     */
    public function __construct($isDev){
        define('IS_DEV', $isDev);
        //TODO $this->cache_manager = new CacheManager();
    }

    /**
     * petition dispatcher
     * asks for the controller to be used and executes it
     */
    public function run(){
        try {
            $this->router();
            $this->execute();
        } catch (Exception $e) {
            $e->showException();
        }
    }

    /**
     * search for the controller to be used
     * depending on the routing and project configuration
     */
    private function router(){
        try {
            $projects = new Projects();
        } catch (Exception $e) {
            throw $e;
        }

        $userLang = $projects->getUserLanguage();
        $project = $projects->getProject();
        $projectFolders = $project->getFolders();

        //load project configs
        $config = Config::getInstance();
        $config->loadConfigs($projectFolders);

        //session first initialization with ID
        $sessionID = $projectFolders[0];
        Session::getInstance($sessionID);

        //language
        $language = new Language($userLang, $project);
        $currentLanguage = $language->getLanguage();
        $config->setDomain( $projects->getDomain($currentLanguage) );

        //routing
        $this->router = new Router( $project->getApp() );
        $this->router->doRouting( $config->get('routing') );
    }

    /**
     * executes the controller of the current petition
     * load its cache (if any)
     * and prints its result
     */
    private function execute(){
        $controllerName = $this->router->getController();
        $this->controller = new $controllerName();

        /*
        $this->controller->setParams($this->router->getParams());

        $cache_def = $this->controller->getCacheDef();
        $html = $this->cache_manager->getCache($cache_def);
        if ($html == null) {
            $html = $this->render();
        }

        echo $html;
        */
    }

    /**
     * if there is no cache, executes the controller and gets its result
     * @return $result    final response of the petition
     */
    private function render(){
        /*
        $view = new Views\View();
        $this->controller->setView($view);

        try {
            $this->controller->build();
        } catch (Exception $e) {
            $e->showException();
        }

        $html = $view->getHTML();
        $this->cache_manager->setCache($html);

        */
        $result = '';
        return $result;
    }

}