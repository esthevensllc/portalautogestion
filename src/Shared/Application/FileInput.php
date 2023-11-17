<?php

namespace AMovil\Shared\Application;

class FileInput
{
    private string $filename;
    private string $filepath;
    private string $extension;

    public function __construct(string $filepath, ?string $filename = null)
    {
        $this->filepath = $filepath;
        if ($filename === null) {
            $this->filename = $this->getFilenameFromFilePath();
        }else{
            $this->filename = $filename;
        }
        $this->setExtension();
    }

    private function setExtension(){
        $filenameParts = explode(".", $this->filename);
        $this->extension = $filenameParts[count($filenameParts)-1];
    }

    public static function create(string $filepath, ?string $filename = null)
    {
        return new self($filepath, $filename);
    }

    public static function createFromFilePath(string $filepath)
    {
        return new self($filepath);
    }

    public function getFilenameFromFilePath(): string
    {
        $fileparts = explode("/", $this->filepath);
        return $fileparts[count($fileparts)-1];
    }

    public function getFilePath(): string {
        return $this->filepath;
    }

    public function getFilename(): string {
        return $this->filename;
    }

    public function getExtension(): string {
        return $this->extension;
    }
}
