<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoFigure extends Model
{
    protected $table = 'metodo_figures';

    protected $fillable = ['metodo_id', 'title', 'figure'];

    public function metodo()
    {
        return $this->belongsTo(Metodo::class);
    }

    public function getImageDataAttribute(): string
    {
        $ext = strtolower(pathinfo($this->title, PATHINFO_EXTENSION));
        $mime = $ext === 'pdf' ? 'application/pdf' : 'image/' . $ext;
        return 'data:' . $mime . ';base64,' . base64_encode($this->figure);
    }
}
