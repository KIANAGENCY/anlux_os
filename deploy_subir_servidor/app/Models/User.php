<?php

namespace App\Models;

use App\Services\ExactoVaultService;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id_tecnico
 * @property string $nombre_tecnico
 * @property string|null $nombre_tecnico_token
 * @property string $correo
 * @property string $contrasena
 * @property string|null $celular
 * @property string $perfil
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 */
class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory, MustVerifyEmail, Notifiable;

    /**
     * Existing legacy table used by Exacto.
     */
    protected $table = 'login';

    /**
     * Legacy primary key.
     */
    protected $primaryKey = 'id_tecnico';

    /**
     * Legacy table does not use timestamps.
     */
    public $timestamps = false;

    protected $fillable = [
        'id_tecnico',
        'nombre_tecnico',
        'nombre_tecnico_token',
        'correo',
        'contrasena',
        'celular',
        'perfil',
        'activo',
        'last_seen_at',
        'email_verified_at',
        'remember_token',
        /** Virtual: se persiste en `contrasena` vía mutador. */
        'password',
    ];

    protected $hidden = [
        'contrasena',
        'password',
        'remember_token',
    ];

    public function isActivo(): bool
    {
        $raw = $this->getAttributes()['activo'] ?? null;
        if ($raw === null) {
            return true;
        }

        return (bool) $raw;
    }

    protected function casts(): array
    {
        return [
            'id_tecnico' => 'integer',
            'activo' => 'boolean',
            'last_seen_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->contrasena ?? '');
    }

    /**
     * Compatibilidad con formularios/tests Breeze (`name` ↔ `nombre_tecnico`).
     */
    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nombre_tecnico'] ?? '');
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['nombre_tecnico'] = $value;
    }

    /**
     * Devuelve nombre técnico legible cuando está sellado (v1:...).
     */
    public function getNombreTecnicoAttribute($value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        if (! str_starts_with($raw, 'v1:')) {
            return $raw;
        }

        $revealed = app(ExactoVaultService::class)->revealString($raw, false);

        return $revealed !== '' ? $revealed : $raw;
    }

    /**
     * Compatibilidad con formularios/tests Breeze (`email` ↔ `correo`).
     */
    public function getEmailAttribute(): string
    {
        return (string) ($this->attributes['correo'] ?? '');
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['correo'] = mb_strtolower(trim($value), 'UTF-8');
    }

    public function setPasswordAttribute(#[\SensitiveParameter] string $value): void
    {
        $this->attributes['contrasena'] = $value;
    }

    /**
     * Alias del PK para código que espera `$user->id` (verificación de email, etc.).
     */
    public function getIdAttribute(): int
    {
        return (int) ($this->attributes['id_tecnico'] ?? 0);
    }

    public static function nombreToken(string $value): string
    {
        return app(ExactoVaultService::class)->tecnicoNombreToken($value);
    }
}
