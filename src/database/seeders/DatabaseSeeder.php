<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Usuario fake para login del PWA NeonPanda.
     * Contrato: temp-notes/api-contract-request.md
     * El cast 'hashed' del modelo User hashea el password al guardar.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'fulano@panda.test'],
            [
                'name'     => 'fulano Demo',
                'password' => '12345678',
            ],
        );
    }
}
