<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FigureInIntro extends Model
{
    protected $table = 'pim_figures_in_intros';
    public $timestamps = false;

    protected $fillable = ['title', 'figure', 'intro_id'];

    /**
     * Relación con la hoja (PimSheet)
     */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(PimSheet::class, 'intro_id', 'id');
    }

    /**
     * Accessor para obtener la imagen en base64
     */
    public function getImageDataAttribute()
    {
        if ($this->figure) {
            return 'data:image/png;base64,' . base64_encode($this->figure);
        }
        return null;
    }
}
