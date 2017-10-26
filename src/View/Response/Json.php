<?php

namespace Core\View\Response;

/**
 * Class Json
 *
 * All Controllers must extend from this controller
 *
 * @package Core\View\Response
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
class Json extends Response {

    /**
     * set the header of the response
     */
    public function __construct(){
        $this->setHeaderType('application/json');
    }

    /**
     * converts the $info to a string with json format
     * @param array $info
     */
    public function initResponse( $info = null ) {
        $this->response = json_encode( $info );
    }

}