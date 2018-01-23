<?php

namespace Core\Model\Push;

abstract class Base {

    protected $result = array();

    public function getResult(){
        return $this->result;
    }

    abstract  public function send();

    abstract public function close();

} 