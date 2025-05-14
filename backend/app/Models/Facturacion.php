<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facturacion extends Model
{
    protected $table = 'facturaciones';

    protected $fillable = [
        'venta_id',
        'fecha_facturacion',
    ];

    protected $casts = [
        'fecha_facturacion' => 'date',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }
}