<?php

namespace AMovil\Shared\EmailNotification\Domain;

class EmailNotification
{
    private array $to;
    private ?string $subject;
    private ?string $message;
    private ?string $view;
    private $with;
    private array $attachments = [];

    public function to(array $to)
    {
        $this->to = $to;
        return $this;
    }

    public function subject(string $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    
    public function view(string $view)
    {
        $this->view = $view;
        return $this;
    }

    public function with($with)
    {
        $this->with = $with;
        return $this;
    }

    public function attach($file, array $options = [])
    {
        $this->attachments[$file] = $options;
        return $this;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getView()
    {
        return $this->view;
    }

    public function getWith()
    {
        return $this->with;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

}
