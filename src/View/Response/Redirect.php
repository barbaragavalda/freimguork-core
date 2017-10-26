<?php

namespace Core\View\Response;
use Core\Exception;

/**
 * Class RedirectResponse
 * @package Core\Views\Response
 */
class Redirect extends Response{

    /**
     * @var string $url. URL to be redirect
     */
    private $url = '';

    /**
     * @var int $status. HTTP code
     */
    private $status = '';

    /**
     * set the header of the response
     * @param $url
     * @param $status
     */
    public function __construct($url, $status){
        $this->url = $url;
        $this->status = $status;
        $this->setHeaderStatus($status);
    }

    /**
     * redirect to an specific URL
     * if mode debug is on, it stops on each redirection to inform to the user that a redirection is going to happen
     * @param array $info . Is always null
     * @throws \Exception
     */
    public function initResponse( $info = null ) {
        if( empty($this->url) ) {
            throw new \Exception("Cannot redirect to an empty URL.");
        }

        if( IS_DEV ){
            $title = '<h1>301 Moved Permanently</h1>';
            if( $this->status == 302 ) $title = '<h1>302 Temporary Redirect</h1>';
            $this->url = htmlspecialchars($this->url, ENT_QUOTES, 'UTF-8');
            $this->response = '<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Redirecting to '.$this->url.'</title>
    </head>
    <body>
        '.$title.'
        <h2>Redirecting to <a href="'.$this->url.'">'.$this->url.'</a></h2>
    </body>
</html>';

        }else {
            $this->setHeaderLocation( $this->url );
        }
    }
}