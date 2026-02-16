<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\LatexHelper;

class Metodo extends Model
{
    protected $table = 'metodos';
    public $timestamps = false;

    protected $fillable = ['title', 'method_tex', 'method_html', 'subtema_id', 'tema_id', 'user_id'];

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id');
    }

    public function subtema()
    {
        return $this->belongsTo(Subtema::class, 'subtema_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function getMethodHtmlProcessedAttribute()
    {
        if ($this->method_tex) {
            return LatexHelper::toHtml($this->method_tex);
        }
        return $this->method_html ? trim($this->method_html) : '';
    }
}
