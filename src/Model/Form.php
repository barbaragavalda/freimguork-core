<?php

namespace Core\Model;

/**
 * Class Form
 *
 * Form validation
 *
 * @package Core\Utils
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 07/11/2017
 */
class Form extends Model {

    protected $error = false;
    protected $send = false;
    protected $form = array();

    public function getForm(){
        return $this->form;
    }

    public function getError(){
        return $this->error;
    }

    public function getSend(){
        return $this->send;
    }

}