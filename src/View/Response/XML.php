<?php

namespace Core\View\Response;

/**
 * Class XML
 * @package Core\Views\Response
 */
class XML extends HTML {

    private $path = null;

    /**
     * set the header of the response
     * @param string $projectFolder     Folder for project
     * @param string $file              Name of the twig file
     * @param string $path              Save to path
     */
    public function __construct($file, $projectFolder, $path = null) {
        parent::__construct($file, $projectFolder, 200);
        $this->setHeaderType('text/html');

        $this->path = $path;
    }

    public function initResponse($info = null) {
        parent::initResponse($info);

        file_put_contents($this->path, $this->response);
    }

}