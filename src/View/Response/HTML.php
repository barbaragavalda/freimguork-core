<?php

namespace Core\View\Response;

use Core\Utils\Config;

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
    private $twigFolders = '';

    /**
     * set the header of the response
     * @param $projectFolder. Folder for project
     * @param $file. Name of the twig file
     */
    public function __construct($file, $projectFolder) {
        $this->file = $file;
        $this->setHeaderType('text/html');

        $this->addViewFolder( DIR_ROOT . 'src/' . $projectFolder . '/View/' );
        $this->addViewFolder( APPACMAN_DIR . 'View/' );
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

        $loader = new \Twig_Loader_Filesystem($this->twigFolders);
        $twig = new \Twig_Environment($loader, $twigConfig);
        $twig->addExtension( new \Twig_Extensions_Extension_I18n() );
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