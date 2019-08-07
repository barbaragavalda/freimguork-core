<?php 

namespace Core\Routing;

/**
 * Class Router
 *
 * Empty router because web is going to redirect to language URL
 *
 * @package Core\Routing
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class RedirectRouter extends Router {

    public function getController(){
        return 'Core\\Controller\\RedirectLang';
    }

    public function getParams(){
        return array();
    }

    public function getParts(){
        return array();
    }

}