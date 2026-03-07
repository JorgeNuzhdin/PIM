<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\LatexHelper;

class Problema extends Model
{
    protected $table = 'pim_problems';
    public $timestamps = false;
    public $incrementing = false;
  

     protected $fillable = [
         'id',
        'difficulty',
        'school_year',
        'title',
        'problem_tex',
        'hints',
        'solution_tex',
        'comments',
        'source',
        'packages',
        'proponent_id',
        'approved',
        'tema_id',
    ];

    // Relación con el proponente (usuario que subió el problema)
    public function proponent()
    {
        return $this->belongsTo(\App\Models\User::class, 'proponent_id');
    }

    // Relación con tags
    public function tags()
    {
        return $this->hasMany(ProblemaTag::class, 'problem_id');
    }
    
    // Obtener el tema a través de tag -> topic_tema
    public function tema()
    {
        return $this->hasManyThrough(
            Tema::class,
            TopicTema::class,
            'topic_title', // Foreign key en topic_tema
            'id',          // Foreign key en temas
            'id',          // Local key en pim_problems (esto se manejará con join)
            'tema_id'      // Local key en topic_tema
        );
    }
 
    public function getProblemHtmlProcessedAttribute()
    {
        if ($this->problem_tex) {
            return LatexHelper::toHtml($this->problem_tex);
        }
        return '';
    }

    public function getSolutionHtmlProcessedAttribute()
    {
        if ($this->solution_tex) {
            return LatexHelper::toHtml($this->solution_tex);
        }
        return '';
    }

    public function errorReports()
    {
        return $this->hasMany(\App\Models\ErrorReport::class, 'problema_id');
    }
}

