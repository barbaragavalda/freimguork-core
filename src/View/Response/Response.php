<?php

namespace Core\View\Response;

abstract class Response
{

    /**
     * @var string $response . Response to be shown to the user
     */
    protected string $response = '';

    /**
     * abstract function that specifies how to render the response
     *
     * @param array $info Variables involved on the view
     */
    abstract public function initResponse(array $info = array()): string;

    /**
     * return the response
     * @return string $html
     */
    public function get(): string
    {
        return $this->response;
    }

    /**
     * sets the header status of HTTP protocol
     *
     * @param int $status Code of the status
     */
    protected function setHeaderStatus(int $status): void
    {
        $this->setHeader('HTTP/1.1', $status);
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
        @header($key . ': ' . $value);
    }

}