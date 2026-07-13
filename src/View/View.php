<?php

namespace Core\View;

use Core\View\Response\CSV;
use Core\View\Response\HTML;
use Core\View\Response\Json;
use Core\View\Response\Redirect;
use Core\View\Response\Response;
use Core\View\Response\XML;
use Psr\Http\Message\ResponseInterface;

/**
 * Class View
 * Response of the petition
 */
class View
{

    /**
     * @var array $info . Content of the variables that the view needs
     */
    private array $info = array();

    /**
     * @var ?string $projectFolder . Folder for project App
     */
    private ?string $projectFolder;

    /**
     * @var ?Response $response . Object response
     */
    private ?Response $response = null;

    public function __construct($projectFolder)
    {
        $this->projectFolder = $projectFolder;
    }

    public function getProjectFolder(): string
    {
        return $this->projectFolder;
    }

    /**
     * returns the final result
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response->get();
    }

    /**
     * set info needed for response render
     */
    public function setInfo(array $info): void
    {
        $this->info = $info;
    }

    /**
     * generates the final response
     */
    private function render(): void
    {
        $this->response->initResponse($this->info);
    }

    /**
     * renders a twig template
     *
     * @param string $file    .     Template name
     * @param int    $status  Code (200, ...)
     * @param array  $headers Extra headers
     */
    public function template(string $file, int $status, array $headers = array()): void
    {
        if (!$this->response) {
            $this->response = new HTML($file, $this->projectFolder, $status, $headers);
            $this->render();
        }
    }

    /**
     * renders a JSON file
     */
    public function json(): void
    {
        if (!$this->response) {
            $this->response = new Json();
            $this->render();
        }
    }

    /**
     * renders an XML with twig template
     *
     * @param string  $file . Template name
     * @param ?string $path . Save to path
     */
    public function xml(string $file, ?string $path = null): void
    {
        if (!$this->response) {
            $this->response = new XML($file, $this->projectFolder, $path);
            $this->render();
        }
    }

    /**
     * redirects to an URL
     *
     * @param string $url    . URL to be redirect
     * @param int    $status . Code of the redirection (301, 302)
     */
    public function redirect(string $url, int $status): void
    {
        if (!$this->response) {
            $this->response = new Redirect($url, $status);
            $this->render();
        }
    }

    /**
     * downloads a csv file
     *
     * @param $tableName . Table name for file name
     */
    public function export($tableName): void
    {
        if (!$this->response) {
            $this->response = new CSV($tableName);
            $this->render();
        }
    }

}