<?php

namespace Core\View\Response;

use Core\Routing\Projects;
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class Mail extends Response
{

    private string $file;

    private string $directory;

    /**
     * set the header of the response
     *
     * @param string  $file          Name of the template file
     * @param ?string $projectFolder Folder of views
     */
    public function __construct(string $file, ?string $projectFolder = null)
    {
        $this->file = $file;

        if ($projectFolder == null) {
            $projects      = new Projects();
            $project       = $projects->getProject();
            $projectFolder = $project->getApp();
        }
        $this->directory = DIR_ROOT . 'src/' . $projectFolder . '/View/';
    }

    /**
     * creates the template with Twig
     * if debug mode is on, the cache is disabled
     * also indicates the variables that can be used on the template
     *
     * @param array $info
     *
     * @return string
     */
    public function initResponse(array $info = array()): string
    {
        $loader = new FilesystemLoader($this->directory);
        $twig   = new Environment($loader, array());
        $twig->addExtension(new Translation());
        $filter = new TwigFilter(
            'trans', function ($context, $string) {
            return Translation::transGetText($string, $context);
        }, ['needs_context' => true]
        );
        $twig->addFilter($filter);
        try {
            $template       = $twig->load($this->file);
            $this->response = $template->render($info);
        } catch (LoaderError|RuntimeError|SyntaxError) {

        }
        return $this->response;
    }
}