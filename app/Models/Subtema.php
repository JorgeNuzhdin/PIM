<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtema extends Model
{
    protected $table = 'subtemas';
    public $timestamps = false;

    protected $fillable = ['nombre', 'tema_id'];

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id');
    }
}
