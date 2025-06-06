<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Localidad extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $table = 'localidades';

    protected $fillable = ['nombre', 'disponibilidad'];
}