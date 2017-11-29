<?php

namespace Core;

include DIR_ROOT .'vendor/appaqui/freimguork-core/src/Utils/NonExistingFunctions.php';

use Core\Controller\CacheManager;
use Core\Routing\Projects;
use Core\Routing\RedirectRouter;
use Core\Routing\Router;
use Core\Utils\Config;

use Core\Utils\Exception;
use Core\Utils\Language;
use Core\Utils\Session;
use Core\View\View;

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

    /**
     * @var string $projectFolder. Folder for project App
     */
    private $projectFolder = null;

    /**
     * @var \Core\Controller\CacheManager $controllerCache.
     */
    private $controllerCache = null;

    /**
     * Bootstrap constructor.
     * @param $isDev    indicates if the environment is development or production
     */
    public function __construct($isDev){
        define('IS_DEV', $isDev);
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
        $hasCustomLanguage = $projects->hasCustomLanguage();
        $project = $projects->getProject();
        $projectFolders = $project->getFolders();

        //load project configs
        $config = Config::getInstance();
        $config->loadConfigs($projectFolders);

        //controller cache
        $this->controllerCache = new CacheManager();

        //language
        $language = new Language($userLang, $project);
        $currentLanguage = $language->getLanguage();
        $config->setDomains( $projects->getDomains($currentLanguage) );
        $config->setLanguage( $currentLanguage );

        if( ($hasCustomLanguage && $userLang) || !$hasCustomLanguage ){
            //session first initialization with ID
            Session::getInstance($config->getDomain());

            $language->initID();

            //routing
            $this->projectFolder = $project->getApp();
            $this->router = new Router( $this->projectFolder );
            $this->router->doRouting( $config->get('routing') );
        }else{
            $this->router = new RedirectRouter();
        }
    }

    /**
     * executes the controller of the current petition
     * load its cache (if any)
     * and prints its result
     */
    private function execute(){
        $controllerName = $this->router->getController();
        $this->controller = new $controllerName();
        $this->controller->setParams( $this->router->getParams() );
        $this->controller->setParts( $this->router->getParts() );

        $cacheDef = $this->controller->getCacheDef();
        $response = $this->controllerCache->getCache($cacheDef);
        if( $response == null ){
            $response = $this->render();
        }

        echo $response;
    }

    /**
     * if there is no cache, executes the controller and gets its result
     * @return $result    final response of the petition
     */
    private function render(){
        $this->controller->setView( new View( $this->projectFolder ) );

        try {
            $this->controller->build();
        } catch (Exception $e) {
            $e->showException();
        }

        $response = $this->controller->getResponse();
        $this->controllerCache->saveCache($response);

        return $response;
    }

}