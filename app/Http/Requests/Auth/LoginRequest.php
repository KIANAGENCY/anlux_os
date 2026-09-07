<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\AnluxAuthContext;
use App\Services\RememberTokenService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\SafeSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->input('email'));
        $password = (string) $this->input('password');
        $nombreUsuario = mb_strtolower($login, 'UTF-8');
        $correoLookup = filter_var($login, FILTER_VALIDATE_EMAIL) !== false
            ? mb_strtolower($login, 'UTF-8')
            : $login;

        $userQuery = User::query()->where('correo', $correoLookup);
        if (SafeSchema::hasColumn('login', 'nombre_usuario')) {
            $userQuery->orWhereRaw('LOWER(TRIM(nombre_usuario)) = ?', [$nombreUsuario]);
        }
        $user = $userQuery->first();

        $allowPlain = filter_var((string) env('ANLUX_LOGIN_ALLOW_PLAINTEXT_PASSWORD', false), FILTER_VALIDATE_BOOL);
        $valid = $user && (
            Hash::check($password, (string) $user->contrasena)
            || ($allowPlain && is_string($user->contrasena) && hash_equals((string) $user->contrasena, $password))
        );

        if (! $valid) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (
            $user
            && SafeSchema::hasColumn('login', 'activo')
            && $user->activo === false
        ) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Esta cuenta está desactivada. Contacta al administrador.',
            ]);
        }

        if ($allowPlain && isset($user) && hash_equals((string) $user->contrasena, $password)) {
            $user->contrasena = Hash::make($password);
            $user->save();
        }

        Auth::login($user, $this->boolean('remember'));

        if ($this->boolean('remember')) {
            app(RememberTokenService::class)->issue($user);
        }

        AnluxAuthContext::syncSessionForUser($user);

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
