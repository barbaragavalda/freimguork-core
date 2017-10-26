<?php 

namespace Core\Routing;

use Core\Utils\Config;
use Core\Utils\StringUtils;

/**
 * Class Router
 *
 * Determines witch controller is going to be initialized depending on the routing.php file
 *
 * @package Core\Routing
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class Router{

    /**
     * @var \Core\Routing\URL $url. object that parses the URL
     */
    private $url = null;

    /**
     * @var string $appFolder. Folder where to find the .php files
     */
    private $appFolder = null;

    public function __construct( $appFolder ){
        $this->url = new URL();
        $this->appFolder = $appFolder;
    }

    public function doRouting( $routing ){
        $this->url->setRouting($routing);
        $this->url->loadParams();
    }

    public function getController(){
        $controller = $this->url->getController();
        $namespace = $this->appFolder . '\\Controller\\';

        if( class_exists($namespace . $controller) ) {
            return $namespace . $controller;
        } else {
            return $namespace . 'DefaultController';
        }
    }

    public function getParams(){
        return $this->url->getParams();
    }

}