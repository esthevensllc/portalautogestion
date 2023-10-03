<?php

namespace AMovil\Shared\Exports\Infrastructure;

use AMovil\Shared\Exports\Domain\Writer;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Ramsey\Uuid\Uuid;

class SpreedSheetWriter implements Writer
{
    private $writer;

    public function __construct(IWriter $writer)
    {
        $this->writer = $writer;    
    }

    public function save($filename, int $flags = 0): void
    {
        $this->writer->save($filename, $flags);
    }

    public function getOutput(): ?string
    {
        $uuid = Uuid::uuid4()->toString();
        $filename = storage_path("app/public/{$uuid}");
        $this->writer->save($filename);
        $contents = file_get_contents($filename);
        unlink($filename);
        return $contents;
    }

    public function saveToTempfile(): string
    {
        $filename = sys_get_temp_dir() ."/phpexport-". Uuid::uuid4()->toString().".tmp";
        // $tempfile = tempnam(sys_get_temp_dir(), "php-tempfilename");
        $this->writer->save($filename);
        return $filename;
    }
}
