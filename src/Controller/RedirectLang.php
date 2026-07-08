<?php

namespace Core\Controller;

use Core\Utils\Config;

class RedirectLang extends Controller
{

    public function build(): void
    {
        $config  = Config::getInstance();
        $oldLang = $config->get('old_lang');

        $domain = $this->domain;
        if (!empty($oldLang)) {
            $domain = $this->rootDomain . $oldLang;
        }

        $uri = $_SERVER['REQUEST_URI'];
        if ($uri == '/') {
            $uri = '';
        }
        $this->redirect($domain . $uri);
    }

}