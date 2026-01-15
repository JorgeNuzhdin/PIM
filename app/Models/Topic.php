<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = 'pim_topics';
    public $timestamps = false;
    
    protected $fillable = ['title'];
}