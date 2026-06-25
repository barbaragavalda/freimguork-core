<?php

namespace Core\View\Response;

use Core\Model\Utils\StringUtils;
use Core\Utils\Config;

class Json extends Response
{

    /**
     * set the header of the response
     */
    public function __construct()
    {
        // add allow origin header
        $config = Config::getInstance();
        $allowOrigins = $config->get('api', 'allow-origin');
        if (is_array($allowOrigins) && count($allowOrigins) && array_key_exists('HTTP_ORIGIN', $_SERVER)) {
            $origin = $_SERVER['HTTP_ORIGIN'];
            if (!StringUtils::endsWidth($origin, '/')) {
                $origin .= '/';
            }

            foreach ($allowOrigins as $allowOrigin) {
                $compareOrigin = $allowOrigin;
                if (!StringUtils::endsWidth($compareOrigin, '/')) {
                    $compareOrigin .= '/';
                }

                if ($compareOrigin == $origin) {
                    $this->setHeader('Access-Control-Allow-Origin', $allowOrigin);
                    break;
                }
            }
        } else {
            $this->setHeader('Access-Control-Allow-Origin', '*');
        }

        $this->setHeader(
            'Access-Control-Allow-Headers',
            'Origin, X-Requested-With, Content-Type, Accept, Authorization, Authorization-Alias'
        );
        $this->setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE');
        $this->setHeader('Connection', 'close');
        $this->setHeader('Accept-Encoding', 'gzip, compress, br');
        $this->setHeaderType('application/json');
    }

    /**
     * converts the $info to a string with json format
     *
     * @param array $info
     *
     * @return string
     */
    public function initResponse(array $info = array()): string
    {
        $this->response = json_encode($info);
        return $this->response;
    }

}
