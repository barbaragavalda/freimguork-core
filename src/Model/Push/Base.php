<?php

namespace Core\Model\Push;

use Core\Model\Model;

abstract class Base extends Model {

    protected $result = array();

    public function getResult(){
        return $this->result;
    }

    abstract  public function send();

    abstract public function close();

} 