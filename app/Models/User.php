<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
     
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'institution',
        'profession',
        'reason',
        'last_login_at',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'    => 'datetime',
            'password' => 'hashed',
        ];
    }


    // Agregar métodos para verificar roles
    public function isAdmin()
    {
        return $this->rol === 'admin';
    }

    public function isEditor()
    {
        return $this->rol === 'editor';
    }

    public function canEditProblemas()
    {
        return in_array($this->rol, ['admin', 'editor']);
    }

    /**
     * ¿Puede subir hojas de problemas (PimSheets)?
     * Incluye a 'profesor_seguro' además de editores/administradores.
     */
    public function canUploadSheets()
    {
        return in_array($this->rol, ['admin', 'editor', 'profesor_seguro']);
    }

    /**
     * ¿Puede editar/eliminar esta hoja concreta?
     * El admin puede con cualquiera; los demás solo con las que subieron ellos.
     */
    public function canManageSheet(PimSheet $sheet): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->canUploadSheets() && (int) $sheet->user_id === (int) $this->id;
    }

    /**
     * ¿Puede subir métodos?
     * Incluye a 'profesor_seguro' además de editores/administradores.
     */
    public function canUploadMetodos()
    {
        return in_array($this->rol, ['admin', 'editor', 'profesor_seguro']);
    }

    /**
     * ¿Puede editar/eliminar este método concreto?
     * Admin y editor pueden con cualquiera; profesor_seguro solo con los que subió él.
     */
    public function canManageMetodo(Metodo $metodo): bool
    {
        if ($this->canEditProblemas()) {
            return true;
        }

        return $this->canUploadMetodos() && (int) $metodo->user_id === (int) $this->id;
    }

    public function isAutoApproved(): bool
    {
        return in_array($this->rol, ['admin', 'editor', 'user_seguro', 'profesor_seguro']);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

}

