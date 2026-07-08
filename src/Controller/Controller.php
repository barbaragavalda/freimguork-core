<?php

namespace Core\Controller;

use Core\Model\Model;
use Core\Utils\Config;
use Core\Utils\Exception;
use Core\Utils\Language;
use Core\View\Extension\Twig;
use Core\View\View;
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * Class Controller
 * All Controllers must extend from this controller
 */
abstract class Controller
{

    /**
     * @var string $domain . Base URL including language (if any)
     */
    protected string $domain = '';

    /**
     * @var string $rootDomain . Base URL without language (if any)
     */
    protected string $rootDomain = '';

    /**
     * @var string $staticDomain . Base URL to static
     */
    protected string $staticDomain = '';

    /**
     * @var array $params . Parameters from URL petition
     */
    protected array $params = array();

    protected View $view;

    private array $headers = array();

    /**
     * @var array $info . Content of the variables that the view needs
     */
    protected array $info = array();

    protected CacheManager $modelCache;

    /**
     * Bootstrap initialization
     */
    public function __construct()
    {
        //domains
        $config           = Config::getInstance();
        $this->domain     = $config->getDomain();
        $this->rootDomain = $config->getBaseDomain();

        $this->assign('domain', $this->domain);
        $this->assign('rootDomain', $this->rootDomain);
        $this->assign('host', $_SERVER['HTTP_HOST']);
        $this->assign('publicIP', $_SERVER['REMOTE_ADDR']);
        $this->assign('isDev', IS_DEV);
        if (!IS_DEV) {
            $this->assign('min', '.min');
        }

        $staticPath         = Config::getWebFolderPrefix() . 'static/';
        $this->staticDomain = $this->rootDomain . $staticPath;
        $this->assign('staticDomain', $this->staticDomain);

        //language
        $culture = $config->getLanguage();
        $this->assign('lang', $culture);
        $this->assign('langLocale', Language::getLocale($culture));

        //cache
        $this->modelCache = new CacheManager();
    }

    /**
     * calls the action method matched by the router
     */
    public function dispatch(string $action): void
    {
        $this->$action();
    }

    /*------------------------------------------
     * VIEW FUNCTIONS
     -------------------------------------------*/
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function setView(View $view): void
    {
        $this->view = $view;
    }

    public function setHeaders($headers): void
    {
        $this->headers = $headers;
    }

    /***
     * saves info that the view needs
     *
     * @param string $var_name
     * @param mixed  $value
     */
    public function assign(string $var_name, mixed $value): void
    {
        $this->info[ $var_name ] = $value;
    }

    /**
     * reset info array
     */
    protected function removeInfo(): void
    {
        $this->info = array();
    }

    /**
     * returns the final result from the view
     */
    public function getResponse(): string
    {
        return $this->view->getResponse();
    }

    /**
     * renders a template file (twig) to HTML format
     *
     * @param string $file   Name of the template file
     * @param int    $status Code (200, ...)
     */
    protected function template(string $file, int $status = 200): void
    {
        $this->view->setInfo($this->info);
        $this->view->template($file, $status, $this->headers);
    }

    /**
     * renders a json
     */
    protected function json(): void
    {
        $this->view->setInfo($this->info);
        $this->view->json();
    }

    /**
     * renders a template file (twig) to xml format
     *
     * @param string $file Name of the template file
     */
    protected function xml(string $file, ?string $path = null): void
    {
        $this->view->setInfo($this->info);
        $this->view->xml($file, $path);
    }

    /**
     * makes a redirection
     *
     * @param string $url    URL to be redirect
     * @param int    $status Code (301, 302)
     */
    protected function redirect(string $url, int $status = 301): void
    {
        $this->view->setInfo($this->info);
        try {
            $this->view->redirect($url, $status);
        } catch (Exception $e) {
            $e->showException();
        }
    }

    protected function export(string $tableName): void
    {
        $this->view->setInfo($this->info);
        $this->view->export($tableName);
    }

    /**
     * returns the value of a parameter on the URL
     *
     * @param string $param
     *
     * @return bool|string
     */
    protected function getParam(string $param): bool|string
    {
        if (array_key_exists($param, $this->params)) {
            return $this->params[ $param ];
        } else {
            return false;
        }
    }

    /*------------------------------------------
     * CACHE FUNCTIONS
     -------------------------------------------*/
    /**
     * controller cache definition
     * return array('ttl' => 300, 'key' => array('controller', __CLASS__));
     * @return array|bool
     */
    public function getCacheDef(): array|bool
    {
        return false;
    }

    /**
     * loads the cache from a model
     *
     * @param Model  $model  object
     * @param string $method method to be called
     * @param array  $params cache params same as function params
     *
     * @return mixed
     */
    protected function loadCache(Model $model, string $method, array $params = array()): mixed
    {
        $cacheDefinition = $model->getCacheDef($method, $params);
        $result          = $this->modelCache->getCache($cacheDefinition);
        if ($result == '') {
            $result = call_user_func_array(array($model, $method), $params);
            $this->modelCache->saveCache($result);
        }
        return $result;
    }

    /**
     * obtain HTML content of twig file
     *
     * @param string  $file
     * @param array   $info
     * @param ?string $path
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function getHTML(string $file, array $info = array(), ?string $path = null): string
    {
        if ($path == null) {
            $path = DIR_ROOT . 'src/' . $this->view->getProjectFolder() . '/View/';
        }

        $loader = new FilesystemLoader(array($path));
        $twig   = new Environment($loader);

        $twig->addExtension(new Translation());
        $filter = new TwigFilter(
            'trans', function ($context, $string) {
            return Translation::transGetText($string, $context);
        }, ['needs_context' => true]
        );
        $twig->addFilter($filter);

        $twig->addExtension(new Twig());
        if (array_key_exists('twig_filters', $info)) {
            foreach ($info['twig_filters'] as $filter) {
                $twig->addFilter($filter);
            }
            unset($info['twig_filters']);
        }

        //response
        $template = $twig->load($file);
        return $template->render($info);
    }

    protected function getCanonicalURL(array $pagination = array(), string $pageKey = 'p'): array
    {
        $page       = 1;
        $parameters = array();
        $query      = $_SERVER['QUERY_STRING'];
        if (!empty($query)) {
            parse_str($query, $parameters);
            if (array_key_exists($pageKey, $parameters)) {
                $page = (int) $parameters[ $pageKey ];
                unset($parameters[ $pageKey ]);
                $query = http_build_query($parameters);
            }
        }

        // final URL without page
        $return    = array();
        $url       = $this->getBaseURL();
        $canonical = $url;
        if (!str_ends_with($canonical, '/')) {
            $canonical .= '/';
        }
        if (!empty($query)) {
            $canonical .= '?' . $query;
        }
        $return['canonical'] = $canonical;

        // prev and next URL
        $current  = $page;
        $lastPage = $page;
        if (count($pagination)) {
            $current = $pagination['current'];
            if (count($pagination['pages'])) {
                $lastPage = $pagination['pages'][ count($pagination['pages']) - 1 ];
            }
        }
        if (($page > 0 && $page != $lastPage) || $current < $lastPage) {
            $parameters['p'] = $page + 1;
            $return['next']  = $url . '?' . http_build_query($parameters);
        }
        if ($page > 1) {
            $parameters['p'] = $page - 1;
            $return['prev']  = $url . '?' . http_build_query($parameters);
        }

        return $return;
    }

    private function getBaseURL(): string
    {
        $canonical = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        if (!empty($_SERVER['REDIRECT_URL'])) {
            $canonical .= $_SERVER['REDIRECT_URL'];
        }
        return $canonical;
    }

}