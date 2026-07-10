<?php

namespace Core;

use Core\Container\Container;
use Core\Controller\CacheManager;
use Core\Controller\Controller;
use Core\Model\MySQL\Manager;
use Core\Model\MySQL\PDO;
use Core\Routing\Exception\MethodNotAllowedException;
use Core\Routing\Exception\RouteNotFoundException;
use Core\Routing\Loader\AttributeRouteLoader;
use Core\Routing\Project;
use Core\Routing\Projects;
use Core\Routing\RouteCollection;
use Core\Routing\RouteCompiler;
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

    /**
     * @var string request path with the current sub-project's resolved domain
     *              prefix (e.g. the language segment) already stripped, so it
     *              lines up with routes/parts that are defined without it
     */
    private string $petitionPath;

    private RouteMatch $routeMatch;

    private Controller $controller;

    private ?string $projectFolder = null;

    private CacheManager $controllerCache;

    private Container $container;

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

        $this->container = new Container();
        Container::setCurrent($this->container);
        $this->container->instance(ServerRequestInterface::class, $this->request);
        $this->container->singleton(Config::class, fn() => Config::getInstance());
        $this->container->singleton(Session::class, fn() => Session::getInstance());
        $this->container->singleton(PDO::class, fn() => Manager::getInstance());
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
        } catch (\Throwable $e) {
            // anything that isn't already a Core\Utils\Exception (a TypeError,
            // a plain \Exception, a PDO failure, ...) would otherwise escape
            // uncaught and let PHP dump a raw stack trace - including file
            // paths - straight to the response, in prod too
            (new Exception($e->getMessage(), 500, $e))->showException();
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
        $this->container->instance(Projects::class, $projects);

        $userLang          = $projects->getUserLanguage();
        $hasCustomLanguage = $projects->hasCustomLanguage();
        $project           = $projects->getProject();
        $projectFolders    = $project->getFolders();
        $language          = new Language($userLang, $project);
        $this->container->instance(Project::class, $project);
        $this->container->instance(Language::class, $language);

        //load project configs
        $config = Config::getInstance();
        $config->loadConfigs($projectFolders);

        //controller cache
        $this->controllerCache = $this->container->make(CacheManager::class);

        //language
        $currentLanguage = $language->getLanguage();
        $config->setDomains($projects->getDomains($currentLanguage));
        $config->setLanguage($currentLanguage);

        // routes/parts are defined relative to the sub-project, without its
        // resolved domain prefix (e.g. the language segment) - strip that
        // prefix from the request path before matching, same as the old
        // URL::loadParams() did against $config->getDomain()
        $domainPrefix       = parse_url($config->getDomain(), PHP_URL_PATH) ?? '';
        $this->petitionPath = RouteCompiler::stripPrefix($this->request->getUri()->getPath(), $domainPrefix);

        if (($hasCustomLanguage && $userLang) || !$hasCustomLanguage) {
            //session first initialization with ID
            Session::getInstance();

            $language->initID();

            //routing
            $this->projectFolder = $project->getApp();
            $router              = new Router($this->loadRoutes($project, $currentLanguage));
            Router::setCurrent($router);

            $matchRequest = $this->request->withUri($this->request->getUri()->withPath($this->petitionPath));

            try {
                $this->routeMatch = $router->match($matchRequest);
            } catch (RouteNotFoundException|MethodNotAllowedException) {
                $this->routeMatch = new RouteMatch(
                    $this->projectFolder . '\\Controller\\DefaultController',
                    'build',
                    array(),
                    'default'
                );
            }
        } else {
            //no language on the URL: redirect to the language-prefixed one
            $this->routeMatch = new RouteMatch('Core\\Controller\\RedirectLang', 'build', array(), 'redirect');
        }
    }

    /**
     * scans (or loads from cache) the RouteCollection of the given sub-project.
     * Cached per language too, not just per app: route paths can contain
     * gettext-translated static segments (see AttributeRouteLoader), so two
     * languages of the same app compile to different paths/regexes.
     */
    private function loadRoutes(Project $project, string $language): RouteCollection
    {
        $app       = $project->getApp();
        $directory = DIR_ROOT . 'src/' . $app . '/Controller/';
        $cacheFile = IS_DEV
            ? null
            : DIR_ROOT . 'src/cache/prod/freimguork/routes-' . $app . '-' . $language . '.php';

        return (new AttributeRouteLoader())->load($app, $directory, $cacheFile);
    }

    /**
     * literal URL path segments for the current request, in order (e.g. "shop/pro/2" => ["shop", "pro", "2"]),
     * relative to the sub-project's domain prefix - same as {@see $petitionPath}
     * @return array<string>
     */
    private function currentParts(): array
    {
        return RouteCompiler::splitPath($this->petitionPath);
    }

    /**
     * executes the controller of the current petition
     * load its cache (if any)
     * and prints its result
     */
    private function execute(): void
    {
        $controllerName   = $this->routeMatch->controllerClass;
        $this->controller = $this->container->make($controllerName);
        $this->controller->setParams($this->routeMatch->params);
        $this->controller->setParts($this->currentParts());

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
