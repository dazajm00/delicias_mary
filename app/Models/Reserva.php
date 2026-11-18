<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Reserva extends Model
{
    protected $table = 'reservas';
    protected $primaryKey = 'id_reserva';
    public $timestamps = false;

    protected $fillable = [
        'id_mesa', 'id_usuario', 'nombre_cliente', 'personas',
        'fecha', 'hora', 'motivo', 'estado', 'creada_en',
    ];

    protected $casts = [
        'fecha'     => 'date',
        'creada_en' => 'datetime',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'id_mesa', 'id_mesa');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function horaTexto(): string
    {
        return Carbon::parse($this->hora)->format('h:i a');
    }

    public function fechaTexto(): string
    {
        if ($this->fecha->isToday()) {
            return 'Hoy';
        }
        if ($this->fecha->isTomorrow()) {
            return 'Mañana';
        }
        return $this->fecha->format('d/m/Y');
    }
}