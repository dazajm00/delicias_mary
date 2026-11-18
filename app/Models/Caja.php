<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'cajas';
    protected $primaryKey = 'id_caja';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario_apertura', 'base', 'abierta_en',
        'id_usuario_cierre', 'cerrada_en',
        'ventas_efectivo', 'ventas_otros', 'entradas', 'salidas',
        'esperado', 'contado', 'diferencia', 'nota',
    ];

    protected $casts = [
        'abierta_en' => 'datetime',
        'cerrada_en' => 'datetime',
    ];

    // La caja que está abierta en este momento (solo puede haber una)
    public static function abierta(): ?self
    {
        return static::whereNull('cerrada_en')->latest('abierta_en')->first();
    }

    public function estaAbierta(): bool
    {
        return $this->cerrada_en === null;
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_caja', 'id_caja');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'id_caja', 'id_caja');
    }

    public function correcciones()
    {
        return $this->hasMany(CorreccionCaja::class, 'id_caja', 'id_caja');
    }

    // Se puede cancelar la apertura solo si todavía no se registró nada en la caja
    public function sePuedeCancelar(): bool
    {
        return $this->estaAbierta()
            && ! $this->pagos()->exists()
            && ! $this->movimientos()->exists();
    }

    public function usuarioApertura()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_apertura', 'id_usuario');
    }

    public function usuarioCierre()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_cierre', 'id_usuario');
    }

    // Total cobrado en esta caja por cada medio de pago: ['Efectivo' => 26000, 'Nequi' => 23000, ...]
    public function ventasPorMedio()
    {
        return $this->pagos()->with('medio')->get()
            ->groupBy(fn ($pago) => $pago->medio->nombre ?? 'Otro')
            ->map->sum('total');
    }

    public function ventasEfectivo(): float
    {
        return (float) $this->pagos()
            ->whereHas('medio', fn ($consulta) => $consulta->where('nombre', 'Efectivo'))
            ->sum('total');
    }

    public function totalEntradas(): float
    {
        return (float) $this->movimientos()->where('tipo', 'ENTRADA')->sum('valor');
    }

    public function totalSalidas(): float
    {
        return (float) $this->movimientos()->where('tipo', 'SALIDA')->sum('valor');
    }

    // Lo que debería haber en el cajón: base + ventas en efectivo + entradas - salidas
    public function efectivoEsperado(): float
    {
        return (float) $this->base + $this->ventasEfectivo() + $this->totalEntradas() - $this->totalSalidas();
    }
}
