<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedioPago extends Model
{
    protected $table = 'medios_pago';
    protected $primaryKey = 'id_medio_pago';
    public $timestamps = false;

    protected $fillable = ['nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];
}