<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Problema;
class Hoja extends Model
{
    protected $table = 'hojas';

    protected $fillable = [
        'user_id',
        'nombre_hoja',
        'nombre_grupo',
        'tema',
        'year',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function problems(): BelongsToMany
    {
        return $this->belongsToMany(Problema::class, 'hoja_problem', 'hoja_id', 'problem_id')
                    ->withPivot('orden')
                    ->orderBy('hoja_problem.orden');
    }
}