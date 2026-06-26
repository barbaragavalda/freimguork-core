<?php

namespace Core\Model;

class Form extends Model
{

    protected bool  $error = false;
    protected bool  $send  = false;
    protected array $form  = array();

    public function getForm(): array
    {
        return $this->form;
    }

    public function getError(): bool
    {
        return $this->error;
    }

    public function getSend(): bool
    {
        return $this->send;
    }

}