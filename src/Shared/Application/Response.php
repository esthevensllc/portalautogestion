<?php

namespace AMovil\Shared\Application;

class Response
{
    private $errors = [];
    private $data;

    public function __construct(array $errors, $data = null)
    {
        $this->errors = $errors;
        $this->data = $data;
    }

    public function data(){
        return $this->data;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function errorsOf($field): array
    {
        if(isset($this->errors[$field])){
            return $this->errors[$field];
        }
        return [];
    }

    public function fieldHasErrors($field){
        return count($this->errorsOf($field)) == 0 ? false : true;
    }

    public function passes(): bool
    {
        return count($this->errors) == 0 ? true : false;
    }

    public function fails(): bool {
        return !$this->passes();
    }

    public function toArray(): array {
        return [
            'passes' => $this->passes(),
            'errors' => $this->errors(),
            'data' => $this->data()
        ];
    }
}
