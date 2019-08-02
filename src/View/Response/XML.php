<?php

namespace Core\View\Response;

use Core\Utils\Config;
use Core\View\Extension\Twig;

/**
 * Class XML
 * @package Core\Views\Response
 */
class XML extends HTML {

    /**
     * set the header of the response
     * @param $projectFolder. Folder for project
     * @param $file. Name of the twig file
     */
    public function __construct($file, $projectFolder) {
        parent::__construct($file, $projectFolder);
        $this->setHeaderType('application/xml');
    }

}