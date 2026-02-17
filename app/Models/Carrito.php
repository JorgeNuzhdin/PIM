<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    protected $table = 'carrito';

    protected $fillable = ['user_id', 'problema_id', 'metodo_id', 'orden'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function problema()
    {
        return $this->belongsTo(Problema::class, 'problema_id');
    }

    public function metodo()
    {
        return $this->belongsTo(Metodo::class, 'metodo_id');
    }

    public function isProblema(): bool
    {
        return $this->problema_id !== null;
    }

    public function isMetodo(): bool
    {
        return $this->metodo_id !== null;
    }
}
