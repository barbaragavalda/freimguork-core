<?php

namespace Core\View\Response;

use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

abstract class Response
{

    /**
     * @var ResponseInterface $response . Response to be shown to the user
     */
    protected ResponseInterface $response;

    public function __construct()
    {
        $this->response = new PsrResponse();
    }

    /**
     * abstract function that specifies how to render the response
     *
     * @param array $info Variables involved on the view
     */
    abstract public function initResponse(array $info = array()): ResponseInterface;

    /**
     * return the response
     */
    public function get(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * sets the HTTP status code of the response
     *
     * @param int $status Code of the status
     */
    protected function setHeaderStatus(int $status): void
    {
        $this->response = $this->response->withStatus($status);
    }

    /**
     * sets the header type of HTTP protocol
     *
     * @param string $type Type of file
     */
    protected function setHeaderType(string $type): void
    {
        $this->setHeader('Content-Type', $type);
    }

    /**
     * sets the header location of the HTTP protocol
     *
     * @param string $url URL to be redirect
     */
    protected function setHeaderLocation(string $url): void
    {
        $this->setHeader('Location', $url);
    }

    /**
     * sets any header parameter
     *
     * @param string $key
     * @param string $value
     */
    protected function setHeader(string $key, string $value): void
    {
        $this->response = $this->response->withHeader($key, $value);
    }

    /**
     * sets the response body
     *
     * @param string $body
     */
    protected function setBody(string $body): void
    {
        $this->response = $this->response->withBody(Utils::streamFor($body));
    }

}
