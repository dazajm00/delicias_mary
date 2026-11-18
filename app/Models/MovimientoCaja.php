<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';
    protected $primaryKey = 'id_movimiento';
    public $timestamps = false;

    // tipo: ENTRADA (entra efectivo al cajón) o SALIDA (sale efectivo del cajón)
    protected $fillable = [
        'id_caja', 'id_usuario', 'tipo', 'valor', 'motivo', 'fecha_hora',
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
