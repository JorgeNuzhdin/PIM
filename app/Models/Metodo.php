<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\LatexHelper;

class Metodo extends Model
{
    protected $table = 'metodos';
    public $timestamps = false;

    protected $fillable = ['title', 'method_tex', 'subtema_ids', 'tema_id', 'user_id', 'institution'];

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Array de IDs de subtemas
     */
    public function getSubtemaIdsArrayAttribute()
    {
        if (empty($this->subtema_ids)) return [];
        return array_map('intval', array_filter(explode(',', $this->subtema_ids)));
    }

    /**
     * Colección de subtemas asociados
     */
    public function getSubtemasAttribute()
    {
        $ids = $this->subtema_ids_array;
        if (empty($ids)) return collect();
        return Subtema::whereIn('id', $ids)->get();
    }

    public function figures()
    {
        return $this->hasMany(\App\Models\MetodoFigure::class, 'metodo_id');
    }

    public function getMethodHtmlProcessedAttribute()
    {
        if ($this->method_tex) {
            return LatexHelper::toHtml($this->method_tex);
        }
        return '';
    }
}
