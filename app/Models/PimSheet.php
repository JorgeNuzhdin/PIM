<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimSheet extends Model
{
    protected $table = 'pim_sheets';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'date_year',
        'access',
        'planet',
        'tex_sols',
        'institution',
        'problems',
        'preambles',
        'theme',
    ];

    /**
     * Relación con el tema
     */
    public function tema(): BelongsTo
    {
        return $this->belongsTo(Tema::class, 'theme', 'id');
    }
}
