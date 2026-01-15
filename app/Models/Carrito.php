<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    protected $table = 'carrito';
    
    protected $fillable = ['user_id', 'problema_id', 'orden'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function problema()
    {
        return $this->belongsTo(Problema::class, 'problema_id');
    }
}