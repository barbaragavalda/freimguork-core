<?php

namespace Core;

use Core\Controller\CacheManager;
use Core\Controller\Controller;
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
 * Main class of the framework.
 */
class Bootstrap
{

    private ?Router $router;

    private ?Controller $controller;

    private string $projectFolder;

    private ?CacheManager $controllerCache;

    /**
     * Bootstrap constructor.
     *
     * @param boolean $isDev indicates if the environment is development or production
     */
    public function __construct(bool $isDev)
    {
        define('IS_DEV', $isDev);
        date_default_timezone_set('Europe/Madrid');
    }

    /**
     * petition dispatcher
     * asks for the controller to be used and executes it
     */
    public function run(): void
    {
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
    private function router(): void
    {
        $projects = new Projects();

        $userLang          = $projects->getUserLanguage();
        $hasCustomLanguage = $projects->hasCustomLanguage();
        $project           = $projects->getProject();
        $projectFolders    = $project->getFolders();
        $language          = new Language($userLang, $project);

        //load project configs
        $config = Config::getInstance();
        $config->loadConfigs($projectFolders);

        //controller cache
        $this->controllerCache = new CacheManager();

        //language
        $currentLanguage = $language->getLanguage();
        $config->setDomains($projects->getDomains($currentLanguage));
        $config->setLanguage($currentLanguage);

        if (($hasCustomLanguage && $userLang) || !$hasCustomLanguage) {
            //session first initialization with ID
            Session::getInstance($config->getBaseDomain());

            $language->initID();

            //routing
            $this->projectFolder = $project->getApp();
            $this->router        = new Router($this->projectFolder);
            $this->router->doRouting($config->get('routing'));
        } else {
            $this->router = new RedirectRouter();
        }
    }

    /**
     * executes the controller of the current petition
     * load its cache (if any)
     * and prints its result
     */
    private function execute(): void
    {
        $controllerName   = $this->router->getController();
        $this->controller = new $controllerName();
        $this->controller->setParams($this->router->getParams());
        $this->controller->setParts($this->router->getParts());

        $cacheDef = $this->controller->getCacheDef();
        $cache    = $this->controllerCache->getCache($cacheDef);
        if ($cache == null) {
            $response = $this->render();
        } else {
            $response = $cache['response'];
            $headers  = $cache['headers'];
            foreach ($headers as $header) {
                header($header);
            }
        }

        echo $response;
    }

    /**
     * if there is no cache, executes the controller and gets its result
     * @return string $result    final response of the petition
     */
    private function render(): string
    {
        $this->controller->setView(new View($this->projectFolder));

        try {
            $this->controller->build();
        } catch (Exception $e) {
            $e->showException();
        }

        $response = $this->controller->getResponse();
        $this->controllerCache->saveCache(array(
            'response' => $response,
            'headers'  => headers_list()
        ));

        return $response;
    }

}