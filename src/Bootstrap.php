<?php

namespace Core;

use Core\Controller\CacheManager;
use Core\Controller\Controller;
use Core\Routing\Exception\MethodNotAllowedException;
use Core\Routing\Exception\RouteNotFoundException;
use Core\Routing\Loader\AttributeRouteLoader;
use Core\Routing\Project;
use Core\Routing\Projects;
use Core\Routing\RouteCollection;
use Core\Routing\RouteMatch;
use Core\Routing\Router;
use Core\Utils\Config;

use Core\Utils\Exception;
use Core\Utils\Language;
use Core\Utils\Session;
use Core\View\View;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Bootstrap
 * Main class of the framework.
 */
class Bootstrap
{

    private ServerRequestInterface $request;

    private ?RouteMatch $routeMatch;

    private ?Controller $controller;

    private ?string $projectFolder = null;

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
        $this->request = ServerRequest::fromGlobals();
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
     * search for the route to be used
     * depending on the current sub-project's controllers and configuration
     * @throws \Exception
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
            Session::getInstance();

            $language->initID();

            //routing
            $this->projectFolder = $project->getApp();
            $router              = new Router($this->loadRoutes($project));
            Router::setCurrent($router);

            try {
                $this->routeMatch = $router->match($this->request);
            } catch (RouteNotFoundException|MethodNotAllowedException) {
                $this->routeMatch = new RouteMatch(
                    $this->projectFolder . '\\Controller\\DefaultController',
                    'handle',
                    array(),
                    'default'
                );
            }
        } else {
            //no language on the URL: redirect to the language-prefixed one
            $this->routeMatch = new RouteMatch('Core\\Controller\\RedirectLang', 'handle', array(), 'redirect');
        }
    }

    /**
     * scans (or loads from cache) the RouteCollection of the given sub-project
     */
    private function loadRoutes(Project $project): RouteCollection
    {
        $app       = $project->getApp();
        $directory = DIR_ROOT . 'src/' . $app . '/Controller/';
        $cacheFile = IS_DEV ? null : DIR_ROOT . 'src/cache/prod/freimguork/routes-' . $app . '.php';

        return (new AttributeRouteLoader())->load($app, $directory, $cacheFile);
    }

    /**
     * executes the controller of the current petition
     * load its cache (if any)
     * and prints its result
     */
    private function execute(): void
    {
        $controllerName   = $this->routeMatch->controllerClass;
        $this->controller = new $controllerName();
        $this->controller->setParams($this->routeMatch->params);

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
            $this->controller->dispatch($this->routeMatch->action);
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
