<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProblemaTag extends Model
{
    protected $table = 'problemas_tags';
    public $timestamps = false;
    
    protected $fillable = ['problem_id', 'tag'];
    
    public function problema()
    {
        return $this->belongsTo(Problema::class, 'problem_id');
    }
}