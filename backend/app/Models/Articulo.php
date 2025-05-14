<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Articulo extends Model
{
    protected $fillable = [
        'user_id',
        'nombre',
        'descripcion',
        'categoria',
        'precio_base',
    ];

    public function variantes(): HasMany
    {
        return $this->hasMany(Variante::class);
    }
}