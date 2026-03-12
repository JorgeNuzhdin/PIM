<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoErrorReport extends Model
{
    protected $table = 'metodo_error_reports';

    protected $fillable = ['metodo_id', 'user_id', 'reporte', 'tipo', 'solved'];

    public function metodo()
    {
        return $this->belongsTo(Metodo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
