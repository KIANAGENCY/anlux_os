<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->name();

        return [
            'id_tecnico' => fake()->unique()->numberBetween(1, 1_000_000),
            'nombre_tecnico' => $nombre,
            'nombre_tecnico_token' => User::nombreToken($nombre),
            'correo' => fake()->unique()->safeEmail(),
            'contrasena' => static::$password ??= Hash::make('password'),
            'celular' => null,
            'perfil' => 'tecnico',
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => 'administrador',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
