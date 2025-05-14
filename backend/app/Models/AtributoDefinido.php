<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtributoDefinido extends Model
{
    protected $table = 'atributos_definidos';

    protected $fillable = [
        'user_id',
        'nombre',
    ];

    public function valores(): HasMany
    {
        return $this->hasMany(ValorAtributo::class, 'atributo_id');
    }
}