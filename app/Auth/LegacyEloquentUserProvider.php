<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Mapea credenciales estándar de Laravel (p. ej. "email" en reset de contraseña)
 * al campo real de la tabla legacy `login`: `correo`.
 */
final class LegacyEloquentUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     * @return (Authenticatable&Model)|null
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        if (isset($credentials['email'])) {
            $credentials['correo'] = $credentials['email'];
            unset($credentials['email']);
        }

        return parent::retrieveByCredentials($credentials);
    }
}
