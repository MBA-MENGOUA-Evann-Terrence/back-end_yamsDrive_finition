<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        // Utilisateur admin
        User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'name' => 'John Doe',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'signature' => 'JohnDoeSignature',
            'telephone1' => '0123456789',
            'telephone2' => '0987654321',
            'permissions' => json_encode(['create', 'edit', 'delete']),
            'role' => 'admin',
            'statut' => 'actif',
        ]);

        // Autres utilisateurs
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'nom' => $faker->lastName,
                'prenom' => $faker->firstName,
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'signature' => $faker->userName,
                'telephone1' => $faker->phoneNumber,
                'telephone2' => $faker->phoneNumber,
                'permissions' => json_encode(['read']),
                'role' => 'agent',
                'statut' => $faker->randomElement(['actif', 'inactif']),
            ]);
        }
    }
}
