<?php

namespace Core\View\Response;

use Core\Routing\Projects;
use Core\View\Response\Response;

/**
 * Class MailResponse
 *
 * Main class for the Mail Responses
 *
 * @package     Core
 * @subpackage  Views
 * @file        MailResponse.php
 * @author      TONI PALANQUES <tpalanques@domain.com>
 * @date        27/04/2016
 */

class Mail extends Response {

    /**
     * @var string $file . Template file
     */
    private $file = '';

    /**
     * @var string $directory . Directory of the template files
     */
    private $directory = '';

    /**
     * set the header of the response
     * @param $file . Name of the template file
     */
    public function __construct($file){
        $this->file = $file;

        $projects = new Projects();
        $project = $projects->getProject();
        $this->directory = DIR_ROOT . 'src/' . $project->getApp() . '/View/';
    }

    /**
     * creates the template with Twig
     * if debug mode is on, the cache is disabled
     * also indicates the variables that can be used on the template
     * @param array $info
     */
    public function initResponse($info = null){
        $loader = new \Twig_Loader_Filesystem($this->directory);
        $twig = new \Twig_Environment($loader, array());
		$twig->addExtension(new \Twig_Extensions_Extension_I18n());
        $template = $twig->load($this->file);
        $this->response = $template->render($info);
    }
}