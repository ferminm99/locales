<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variante extends Model
{
    protected $table = 'variantes';

    protected $fillable = [
        'articulo_id',
        'sku',
        'stock',
        'precio',
        'atributos',
    ];

    protected $casts = [
        'atributos' => 'array',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}