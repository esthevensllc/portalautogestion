<?php

namespace AMovil\Shared\Domain;

class SimpleDomain
{
    private $id;
    private $label;

    public function __construct($id, $label)
    {
        $this->id = $id;
        $this->label = $label;
    }

    public function getId(){
        return $this->id;
    }

    public function getLabel(){
        return $this->label;
    }
}
