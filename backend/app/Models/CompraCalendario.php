<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraCalendario extends Model
{
    protected $table = 'compras_calendario';

    protected $fillable = [
        'user_id',
        'nombre_persona',
        'fecha',
        'descripcion',
        'hora_inicio',
        'hora_fin',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'hora_inicio' => 'string',
        'hora_fin'    => 'string',
    ];
}