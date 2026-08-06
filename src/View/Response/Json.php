<?php

namespace Core\View\Response;

use Core\Model\Utils\StringUtils;
use Core\Utils\Config;
use Psr\Http\Message\ResponseInterface;

class Json extends Response
{

    /**
     * set the header of the response
     */
    public function __construct()
    {
        parent::__construct();

        // add allow origin header
        $config = Config::getInstance();
        $allowOrigins = $config->get('api', 'allow-origin');
        if (is_array($allowOrigins) && count($allowOrigins) && array_key_exists('HTTP_ORIGIN', $_SERVER)) {
            $origin = $_SERVER['HTTP_ORIGIN'];
            if (!str_ends_with($origin, '/')) {
                $origin .= '/';
            }

            foreach ($allowOrigins as $allowOrigin) {
                $compareOrigin = $allowOrigin;
                if (!str_ends_with($compareOrigin, '/')) {
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
            'Origin, X-Requested-With, Content-Type, Accept, Authorization, Authorization-Alias, Accept-Language'
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
     */
    public function initResponse(array $info = array()): ResponseInterface
    {
        // `error` doubles as an HTTP status code when it's an int -
        // currently only ever 401, from WebserviceController::checkToken()
        // (see that method's own docblock) failing to resolve the request's
        // token to a real user. Every response used to stay a plain 200
        // regardless, with the failure only ever visible inside the JSON
        // body itself - a client had no transport-level way to tell "your
        // session is gone" apart from any other error without inspecting
        // every response body by hand, and in practice nothing did.
        if (is_int($info['error'] ?? null)) {
            $this->setHeaderStatus($info['error']);
        }
        $this->setBody(json_encode($info));
        return $this->response;
    }

}
