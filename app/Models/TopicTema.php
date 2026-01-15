<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicTema extends Model
{
    protected $table = 'topic_tema';
    public $timestamps = false;
    
    protected $fillable = ['topic_title', 'tema_id'];
}