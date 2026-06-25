<?php

namespace Core\View\Response;

use Exception;

class Redirect extends Response
{

    /**
     * @var string $url . URL to be redirect
     */
    private string $url;

    /**
     * @var int $status . HTTP code
     */
    private int $status;

    /**
     * set the header of the response
     *
     * @param string $url
     * @param int    $status
     */
    public function __construct(string $url, int $status)
    {
        $this->url    = $url;
        $this->status = $status;
        $this->setHeaderStatus($status);
    }

    /**
     * redirect to a specific URL
     * if mode debug is on, it stops on each redirection to inform to the user that a redirection is going to happen
     *
     * @param array $info Is always null
     *
     * @throws \Exception
     */
    public function initResponse(array $info = array()): string
    {
        if (empty($this->url)) {
            throw new Exception('Cannot redirect to an empty URL.');
        }

        if (IS_DEV) {
            $title          = '<h1>' . $this->status . ' ' . $this->getMessage() . '</h1>';
            $this->url      = htmlspecialchars($this->url, ENT_QUOTES, 'UTF-8');
            $this->response = "
                <!DOCTYPE html>
                <html lang='en'>
                    <head>
                        <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
                        <title>Redirecting to $this->url</title>
                    </head>
                    <body>
                        $title
                        <h2>Redirecting from {$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}
                        to <a href='$this->url'>$this->url</a></h2>
                    </body>
                </html>
            ";
        } else {
            $this->setHeaderLocation($this->url);
        }
        return $this->response;
    }

    private function getMessage(): string
    {
        return match ($this->status) {
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            default => 'Undefined message',
        };
    }

}