<?php

namespace Core\View;
use Core\Utils\Exception;
use Core\View\Response\CSV;
use Core\View\Response\HTML;
use Core\View\Response\HTMLResponse;
use Core\View\Response\Json;
use Core\View\Response\Redirect;

/**
 * Class View
 *
 * Response of the petition
 *
 * @package Core\Controller
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
class View{

    /**
     * @var array $info. Content of the variables that the view needs
     */
    private $info = array();

    /**
     * @var string $projectFolder. Folder for project App
     */
    private $projectFolder = null;

    /**
     * @var object $response. Object response
     */
    private $response = null;

    public function __construct($projectFolder){
        $this->projectFolder = $projectFolder;
    }

    /**
     * returns the final result
     */
    public function getResponse() {
        return $this->response->get();
    }

    /**
     * set info needed for repsponse render
     */
    public function setInfo($info) {
        return $this->info = $info;
    }

    /**
     * generates the final response
     */
    private function render() {
        $this->response->initResponse( $this->info );
    }

    /**
     * renders a twig template
     * @param string $file. Temaplte name
     */
    public function template( $file ) {
        $this->response = new HTML($file, $this->projectFolder);
        $this->render();
    }

    /**
     * renders a json file
     */
    public function json() {
        $this->response = new Json();
        $this->render();
    }

    /**
     * redirects to an URL
     * @param string $url. URL to be redirect
     * @param int $status. Code of the redirection (301, 302)
     * @throws exception
     */
    public function redirect( $url, $status ) {
        try{
            $this->response = new Redirect($url, $status);
            $this->render();
        } catch (Exception $e) {
            $e->showException();
        }
    }

    /**
     * downloads a csv file
     * @param $tableName. Table name for file name
     */
    public function export($tableName){
        $this->response = new CSV($tableName);
        $this->render();
    }

}