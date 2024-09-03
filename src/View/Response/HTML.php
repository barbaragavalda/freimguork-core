<?php

namespace Core\View\Response;

use Core\Utils\Config;
use Core\View\Extension\Twig;
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * Class HTMLResponse
 * @package Core\Views\Response
 */
class HTML extends Response {

    /**
     * @var string $file . Template file
     */
    private $file = '';

    /**
     * @var array $twigFolders . Directory of the template files
     */
    private $twigFolders = array();

    /**
     * set the header of the response
     * @param string $file              Name of the twig file
     * @param string $projectFolder     Folder for project
     * @param int $status               Code (200, ...)
     * @param array $headers            Extra headers
     */
    public function __construct($file, $projectFolder, $status = 200, $headers = array()) {
        $this->file = $file;
        $this->setHeaderType('text/html');
        $this->setHeader('Accept-Encoding', 'gzip, compress, br');
        foreach ($headers as $key => $value){
            $this->setHeader($key, $value);
        }

        $this->addViewFolder( DIR_ROOT . 'src/' . $projectFolder . '/View/' );
        if( $projectFolder == 'Appacman' ) $this->addViewFolder( APPACMAN_DIR . 'View/' );

        $this->setHeaderStatus($status);
    }

    private function addViewFolder($folder){
        if( is_dir($folder) ){
            $this->twigFolders[] = $folder;
        }
    }

    /**
     * creates the template with Twig
     * if debug mode is on, the cache is disabled
     * also indicates the variables that can be used on the template
     * @param array $info
     */
    public function initResponse($info = null) {
        //cache configuration
        $config = Config::getInstance();
        $twigConfig = array();
        if( $config->get('cache', 'is_caching') ){
            $environment = IS_DEV ? 'dev' : 'prod';
            $twigConfig = array(
                'cache' => DIR_ROOT . 'src/cache/' . $environment . '/twig'
            );
        }

        $loader = new FilesystemLoader($this->twigFolders);
        $twig = new Environment($loader, $twigConfig);
        $twig->addExtension( new Twig() );

        // translations
        $twig->addExtension( new Translation() );
        $filter = new TwigFilter(
            'trans',
            function ($context, $string) {
                return Translation::transGetText($string, $context);
            },
            ['needs_context' => true]
        );
        $twig->addFilter($filter);

        if( array_key_exists('twig_filters', $info) ){
            foreach($info['twig_filters'] as $filter){
                $twig->addFilter($filter);
            }
            unset($info['twig_filters']);
        }

        //response
        $template = $twig->load($this->file);
        $this->response = $template->render($info);
    }

}