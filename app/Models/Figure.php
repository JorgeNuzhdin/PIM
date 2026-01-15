<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Figure extends Model
{
    protected $table = 'pim_figures';
    public $timestamps = false;
    
    protected $fillable = ['title', 'figure'];
    
    // Accessor para obtener la imagen en base64
    public function getImageDataAttribute()
    {
        if ($this->figure) {
            return 'data:image/png;base64,' . base64_encode($this->figure);
        }
        return null;
    }
}