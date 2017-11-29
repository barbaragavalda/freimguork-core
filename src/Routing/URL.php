<?php

namespace Core\Routing;

use Core\Model\Utils\StringUtils;
use Core\Utils\Config;

/**
 * Class URL
 *
 * Helper for URL functions
 *
 * @package Core\Routing
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class URL{

    private $userURL = null;
    private $protocol = 'http://';

    private $routing = array();
    private $controller = null;
    private $parts = array();
    private $params = array();

    public function __construct(){
        $this->userURL = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if( substr($this->userURL, -1) != '/' ) $this->userURL .= '/';

        $this->protocol = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://';
    }

    public function getUserURL(){
        return $this->userURL;
    }

    public function getProtocol(){
        return $this->protocol;
    }

    public function getFullUserURL(){
        return $this->protocol . $this->userURL;
    }

    public function getController(){
        return $this->controller;
    }

    public function getParams(){
        return $this->params;
    }

    public function getParts(){
        return $this->parts;
    }

    public function setRouting($routing){
        $this->routing = $routing;
    }

    /**
     * check which route matches the user request
     */
    public function loadParams(){
        $config = Config::getInstance();
        $domain = $config->getDomain();
        $userURL = $this->getFullUserURL();
        $userPetition = str_replace($domain, '', $userURL);

        $routingRegExp = $this->prepareRouting();
        foreach($routingRegExp as $regExp){
            if( preg_match($regExp['regExp'], $userPetition) === 1 ){
                //controller found
                $this->controller = $regExp['controller'];

                //init parameters
                $explodeRouting = explode('/', $regExp['petition']);
                $explodeUser = explode('/', $userPetition);
                for($i=0; $i<count($explodeRouting); $i++){
                    if( StringUtils::startsWidth($explodeRouting[$i], '{') ){
                        $paramValue = $explodeUser[$i];
                        $paramName = str_replace('{', '', $explodeRouting[$i]);
                        $paramName = str_replace('}', '', $paramName);
                        $this->params[$paramName] = $paramValue;
                    }else{
                        $this->parts[] = $explodeUser[$i];
                    }
                }
                break;
            }
        }
    }

    /**
     * prepare the regular expressions
     * @return array
     */
    private function prepareRouting(){
        $routingRegExp = array();

        if( $this->routing ){

            foreach($this->routing as $petition => $controller){
                $regExp = '';
                if( $petition == '' ){
                    $regExp .= '$^';
                }else{
                    $explode = explode('/', $petition);
                    foreach($explode as $part){
                        if( $regExp != '' ) $regExp .= '(/)';
                        if( StringUtils::startsWidth($part, '{') ){
                            $regExp .= '(\w+)';
                        }else{
                            if( $part == '' ){
                                $regExp .= '$^';
                            }else{
                                $regExp .= '(' . $part . ')';
                            }
                        }
                    }
                    if( !StringUtils::endsWidth($regExp, '(/)') ) $regExp .= '(/)';
                }

                $routingRegExp[] = array(
                    'controller' => $controller,
                    'petition' => $petition,
                    'regExp' => '/^' . str_replace('/', '\/', $regExp) . '$/'
                );
            }
        }

        return $routingRegExp;
    }

}