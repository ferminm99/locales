<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorAtributo extends Model
{
    protected $table = 'valores_atributo';

    protected $fillable = [
        'atributo_id',
        'valor',
    ];

    public function atributo(): BelongsTo
    {
        return $this->belongsTo(AtributoDefinido::class, 'atributo_id');
    }
}