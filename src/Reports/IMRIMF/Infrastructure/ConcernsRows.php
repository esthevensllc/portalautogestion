<?php

namespace AMovil\Reports\IMRIMF\Infrastructure;

trait ConcernsRows
{
    protected function rowValue(object $row, string $key)
    {
        $data = (array) $row;

        foreach ([$key, strtolower($key), strtoupper($key)] as $candidate) {
            if (array_key_exists($candidate, $data)) {
                return $data[$candidate];
            }
        }

        return null;
    }
}
