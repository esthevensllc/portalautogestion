<?php

namespace AMovil\Shared\Exports\Domain;

interface ExportService
{
    public function reset();
    public function loadData($headers, $data, $options = []);
    public function makeChart($data, $options);
    public function getExportReference();
    public function download($filename);
    public function getWriter($type): Writer;
}
