<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tema extends Model
{
    protected $table = 'temas';
    public $timestamps = false;
    
    protected $fillable = ['tema'];
    
    // Relación con topics
    public function topics()
    {
        return $this->hasMany(TopicTema::class, 'tema_id');
    }

    public function subtemas()
    {
        return $this->hasMany(Subtema::class, 'tema_id');
    }
}