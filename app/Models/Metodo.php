<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\LatexHelper;

class Metodo extends Model
{
    protected $table = 'metodos';
    public $timestamps = false;

    protected $fillable = ['title', 'method_tex', 'method_html', 'subtema_ids', 'tema_id', 'user_id'];

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

    public function getMethodHtmlProcessedAttribute()
    {
        if ($this->method_tex) {
            return LatexHelper::toHtml($this->method_tex);
        }
        return $this->method_html ? trim($this->method_html) : '';
    }
}
