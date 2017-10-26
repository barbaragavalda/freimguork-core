<?php

namespace Core\Routing;

/**
 * Class Project
 *
 * Project structure
 *
 * @package Core\Routing
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class Project{

    private $url = '';
    private $regularExpression = '';
    private $langPosition = 0;

    private $app = '';
    private $folders = array();
    private $languages = array();

    public function isEmpty(){
        return $this->url == '';
    }

    public function getURL(){
        return $this->url;
    }

    public function getRegularExpression(){
        return $this->regularExpression;
    }

    public function getLangPosition(){
        return $this->langPosition;
    }

    public function getApp(){
        return $this->app;
    }

    public function getFolders(){
        return $this->folders;
    }

    public function getLanguages(){
        return $this->languages;
    }

    public function setURL($url){
        $this->url = $url;
    }

    public function setRegularExpression($regularExpression){
        $this->regularExpression = $regularExpression;
    }

    public function setLangPosition($position){
        $this->langPosition = $position;
    }

    public function setInfo($info){
        $this->app = $info['app'];
        $this->folders = $info['folders'];
        $this->languages = $info['languages'];
    }

}