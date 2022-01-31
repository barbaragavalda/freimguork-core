<?php

namespace Core\Controller;

use Core\Utils\Config;

class RedirectLang extends Controller
{

    public function build()
    {
        $config = Config::getInstance();
        $oldLang = $config->get('old_lang');
        
        $domain = $this->domain;
        if( !empty($oldLang) ){
            $domain = $this->rootDomain . $oldLang;
        }
        $this->redirect($domain . $_SERVER['REQUEST_URI']);
    }

}