<?php

namespace Core\View\Response;

use Core\Routing\Projects;
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

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
     * @param string $file              Name of the template file
     * @param string $projectFolder     Folder of views
     */
    public function __construct($file, $projectFolder = null){
        $this->file = $file;

        if( $projectFolder == null ){
            $projects = new Projects();
            $project = $projects->getProject();
            $projectFolder = $project->getApp();
        }
        $this->directory = DIR_ROOT . 'src/' . $projectFolder . '/View/';

    }

    /**
     * creates the template with Twig
     * if debug mode is on, the cache is disabled
     * also indicates the variables that can be used on the template
     * @param array $info
     */
    public function initResponse($info = null){
        $loader = new FilesystemLoader($this->directory);
        $twig = new Environment($loader, array());
		$twig->addExtension(new Translation());
        $filter = new TwigFilter(
            'trans',
            function ($context, $string) {
                return Translation::transGetText($string, $context);
            },
            ['needs_context' => true]
        );
        $twig->addFilter($filter);
        $template = $twig->load($this->file);
        $this->response = $template->render($info);
    }
}