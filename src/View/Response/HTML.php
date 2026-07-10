<?php

namespace Core\View\Response;

use Core\Routing\Router;
use Core\Utils\Config;
use Core\View\Extension\Twig;
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class HTML extends Response
{

    private string $file;

    private array $twigFolders = array();

    /**
     * set the header of the response
     *
     * @param string $file          Name of the twig file
     * @param string $projectFolder Folder for project
     * @param int    $status        Code (200, ...)
     * @param array  $headers       Extra headers
     */
    public function __construct(string $file, string $projectFolder, int $status = 200, array $headers = array())
    {
        $this->file = $file;
        $this->setHeaderType('text/html');
        $this->setHeader('Accept-Encoding', 'gzip, compress, br');
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }

        $this->addViewFolder(DIR_ROOT . 'src/' . $projectFolder . '/View/');
        if ($projectFolder == 'Appacman') {
            $this->addViewFolder(APPACMAN_DIR . 'View/');
        }

        $this->setHeaderStatus($status);
    }

    private function addViewFolder(string $folder): void
    {
        if (is_dir($folder)) {
            $this->twigFolders[] = $folder;
        }
    }

    /**
     * creates the template with Twig
     * if debug mode is on, the cache is disabled
     * also indicates the variables that can be used on the template
     */
    public function initResponse(array $info = array()): string
    {
        //cache configuration
        $config     = Config::getInstance();
        $twigConfig = array();
        if ($config->get('cache', 'is_caching')) {
            $environment = IS_DEV ? 'dev' : 'prod';
            $twigConfig  = array(
                'cache' => DIR_ROOT . 'src/cache/' . $environment . '/twig',
                'debug' => IS_DEV,
            );
        }

        $loader = new FilesystemLoader($this->twigFolders);
        $twig   = new Environment($loader, $twigConfig);
        $twig->addExtension(new Twig(Router::getCurrent(), $config));

        // translations
        $twig->addExtension(new Translation());
        if(IS_DEV) {
            $twig->addExtension(new DebugExtension());
        }
        $filter = new TwigFilter(
            'trans', function ($context, $string) {
            return Translation::transGetText($string, $context);
        }, ['needs_context' => true]
        );
        $twig->addFilter($filter);

        if (array_key_exists('twig_filters', $info)) {
            foreach ($info['twig_filters'] as $filter) {
                $twig->addFilter($filter);
            }
            unset($info['twig_filters']);
        }

        //response
        try {
            $template       = $twig->load($this->file);
            $this->response = $template->render($info);
        } catch (\Throwable $e) {
            throw $e;
        }
        return $this->response;
    }

}