<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    protected $fillable = [
        'user_id',
        'cliente_id',
        'variante_id',
        'fecha',
        'forma_pago',
        'precio',
        'costo_original',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function variante(): BelongsTo
    {
        return $this->belongsTo(Variante::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}