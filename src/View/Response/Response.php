<?php

namespace Core\View\Response;

/**
 * Class View
 *
 * Response of the petition
 *
 * @package Core\Controller
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
abstract class Response{

    /**
     * @var string $response. Response to be shown to the user
     */
    protected $response = '';

    /**
     * abstract function that specifies how to render the response
     * @param array $info. Variables involved on the view
     */
    abstract public function initResponse( $info = null );

    /**
     * return the response
     * @return string $html
     */
    public function get(){
        return $this->response;
    }

    /**
     * sets the header status of HTTP protocol
     * @param int $status. Code of the status
     */
    protected function setHeaderStatus( $status ){
        $this->setHeader('HTTP/1.1', $status);
    }

    /**
     * sets the header type of HTTP protocol
     * @param string $type. Type of file
     */
    protected function setHeaderType( $type ){
        $this->setHeader('Content-Type', $type);
    }

    /**
     * sets the header location of the HTTP protocol
     * @param string $url. URL to be redirect
     */
    protected function setHeaderLocation( $url ){
        $this->setHeader('Location', $url);
    }

    /**
     * sets any header parameter
     * @param $key
     * @param $value
     */
    protected function setHeader($key, $value){
        header($key . ': ' . $value);
    }

}