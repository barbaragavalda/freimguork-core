<?php

namespace Core\View\Response;

use Core\Utils\Config;

require_once DIR_ROOT . '/vendor/twig/twig/lib/Twig/Autoloader.php';
require_once DIR_ROOT . '/vendor/twig/extensions/lib/Twig/Extensions/Autoloader.php';

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
     * @var string $projectFolder. Folder for project App
     */
    private $projectFolder = null;

    /**
     * @var string $directory . Directory of the template files
     */
    private $folder = '';

    /**
     * set the header of the response
     * @param $projectFolder. Folder for project
     * @param $file. Name of the twig file
     */
    public function __construct($file, $projectFolder) {
        $this->file = $file;
        $this->projectFolder = $projectFolder;
        $this->folder = DIR_ROOT . 'src/' . $this->projectFolder . '/';
        $this->setHeaderType('text/html');
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
        $twig_config = array();
        if( $config->get('cache', 'is_caching') ){
            $environment = IS_DEV ? 'dev' : 'prod';
            $twig_config = array(
                'cache' => $this->folder . 'cache/' . $environment . '/twig'
            );
        }

        //create twig
        $loader = new \Twig_Loader_Filesystem($this->folder . 'View/');
        $twig = new \Twig_Environment($loader, $twig_config);
        $twig->addExtension( new \Twig_Extensions_Extension_I18n() );

        //response
        $template = $twig->load($this->file);
        $this->response = $template->render($info);
    }
}