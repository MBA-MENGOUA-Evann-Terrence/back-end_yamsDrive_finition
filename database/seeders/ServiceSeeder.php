<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vider la table avant d'insérer (optionnel)
        // DB::table('services')->truncate();

        $services = [
            [
                'nom' => 'Direction Générale',
                'description' => 'Service de direction et gestion globale de l\'entreprise',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Secrétariat',
                'description' => 'Service administratif et gestion documentaire',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Logistique',
                'description' => 'Gestion des ressources matérielles et approvisionnement',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Accueil',
                'description' => 'Service d\'accueil et orientation des visiteurs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Ressources Humaines',
                'description' => 'Gestion du personnel et recrutement',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Comptabilité',
                'description' => 'Gestion financière et comptable',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Informatique',
                'description' => 'Support technique et développement informatique',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Marketing',
                'description' => 'Communication et stratégie marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insérer les services
        foreach ($services as $service) {
            Service::create($service);
        }

        $this->command->info('Services créés avec succès !');
    }
}
