<?php

namespace Core\View\Response;

use Core\Model\Utils\StringUtils;
use Core\Utils\Config;

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
        // add allow origin header
        $config = Config::getInstance();
        $allowOrigins = $config->get('api', 'allow-origin');
        if( is_array($allowOrigins) && count($allowOrigins) && array_key_exists('HTTP_ORIGIN', $_SERVER) ){
            $origin = $_SERVER['HTTP_ORIGIN'];
            if( !StringUtils::endsWidth($origin, '/') ) $origin .= '/';

            foreach($allowOrigins as $allowOrigin){
                $compareOrigin = $allowOrigin;
                if( !StringUtils::endsWidth($compareOrigin, '/') ) $compareOrigin .= '/';

                if( $compareOrigin == $origin ){
                    $this->setHeader('Access-Control-Allow-Origin', $allowOrigin);
                    break;
                }
            }
        }

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