<?php

namespace Core\View\Response;

use Psr\Http\Message\ResponseInterface;

class XML extends HTML
{

    private string $path;

    /**
     * set the header of the response
     *
     * @param string  $projectFolder Folder for project
     * @param string  $file          Name of the twig file
     * @param ?string $path          Save to path
     */
    public function __construct(string $file, string $projectFolder, ?string $path = null)
    {
        parent::__construct($file, $projectFolder);
        $this->setHeaderType('text/html');

        $this->path = $path;
    }

    public function initResponse(array $info = array()): ResponseInterface
    {
        parent::initResponse($info);

        file_put_contents($this->path, (string) $this->response->getBody());
        return $this->response;
    }

}