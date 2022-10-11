<?php

namespace App\Exports;

use AMovil\Auth\User\Infrastructure\Repository\User;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportTest implements FromCollection
{
    public function collection()
    {
        return User::all();
    }
}
