<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorReport extends Model
{
    protected $table = 'error_reports';

    protected $fillable = ['problema_id', 'user_id', 'reporte', 'tipo'];

    public function problema()
    {
        return $this->belongsTo(Problema::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
