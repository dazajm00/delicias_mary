<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';
    protected $primaryKey = 'id_pago';
    public $timestamps = false;

    protected $fillable = [
        'numero', 'id_mesa', 'id_usuario', 'id_medio_pago',
        'total', 'monto_recibido', 'cambio', 'fecha_hora', 'id_caja',
    ];

    protected $casts = ['fecha_hora' => 'datetime'];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'id_mesa', 'id_mesa');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // Caja (turno) en la que se registró el cobro
    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    public function medio()
    {
        return $this->belongsTo(MedioPago::class, 'id_medio_pago', 'id_medio_pago');
    }

        // Total del pedido: suma de todas sus líneas (platos, adicionales y productos)
    public function total(): float
    {
        return (float) $this->detalles->sum(fn ($d) => $d->cantidad * $d->precio_unitario);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_pago', 'id_pago');
    }
}