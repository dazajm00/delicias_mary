<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mesa extends Model
{
    use HasFactory;

    protected $table = 'mesas';
    protected $primaryKey = 'id_mesa';
    public $timestamps = false;

    protected $fillable = [
        'numero', 'capacidad', 'estado', 'activa'
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    // Estados de un pedido que todavía no se ha entregado (regla R3)
    public const ESTADOS_EN_CURSO = ['PENDIENTE', 'EN_PREPARACION', 'LISTO'];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_mesa', 'id_mesa');
    }

    public function pedidosEnCurso()
    {
        return $this->pedidos()->whereIn('estado', self::ESTADOS_EN_CURSO)->whereNull('id_pago');
    }

    // Pedidos que todavía no se han cobrado (los cancelados no cuentan)
    public function pedidosPorCobrar()
    {
        return $this->pedidos()->where('estado', '!=', 'CANCELADO')->whereNull('id_pago');
    }

    public function tienePedidosEnCurso(): bool
    {
        return $this->pedidosEnCurso()->exists();
    }

        public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_mesa', 'id_mesa');
    }

    // Reservas activas de hoy que ya están dentro de la hora de aviso (1 hora antes)
    public function reservasVigentes()
    {
        $limite = now()->addHour();
        $hasta  = $limite->isToday() ? $limite->format('H:i:s') : '23:59:59';

        return $this->reservas()
            ->where('estado', 'ACTIVA')
            ->whereDate('fecha', today())
            ->whereTime('hora', '<=', $hasta);
    }
}