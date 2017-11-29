<?php

namespace Core\Controller;

class RedirectLang extends Controller {

    public function build(){
        $this->redirect($this->domain);
    }

}