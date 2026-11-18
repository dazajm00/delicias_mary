<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorreccionCaja extends Model
{
    protected $table = 'correcciones_caja';
    protected $primaryKey = 'id_correccion';
    public $timestamps = false;

    protected $fillable = [
        'id_caja', 'id_usuario', 'base_anterior', 'base_nueva', 'motivo', 'fecha_hora',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
