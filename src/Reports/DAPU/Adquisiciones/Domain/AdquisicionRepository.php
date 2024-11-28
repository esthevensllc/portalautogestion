<?php

namespace AMovil\Reports\DAPU\Adquisiciones\Domain;

interface AdquisicionRepository
{
    public function getByImeis(array $imeis);
}
