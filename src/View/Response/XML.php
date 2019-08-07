<?php

namespace Core\View\Response;

/**
 * Class XML
 * @package Core\Views\Response
 */
class XML extends HTML {

    /**
     * set the header of the response
     * @param string $projectFolder     Folder for project
     * @param string $file              Name of the twig file
     */
    public function __construct($file, $projectFolder) {
        parent::__construct($file, $projectFolder);
        $this->setHeaderType('application/xml');
    }

}