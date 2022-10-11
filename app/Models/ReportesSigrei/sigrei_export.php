<?php
namespace App\Models\ReportesSigrei;

use Illuminate\Database\Eloquent\Model;
class sigrei_export extends Model {

    protected $connection = 'mysql';
    protected $table = 'reporte_sigrei_web';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
}