<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LogActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Désactiver les observers pour éviter les boucles si les actions de seeder sont elles-mêmes loguées
        \App\Models\LogAction::flushEventListeners();

        $users = \App\Models\User::pluck('id')->toArray();
        if (empty($users)) {
            $this->command->info('Aucun utilisateur trouvé, veuillez d\'abord lancer le seeder des utilisateurs.');
            return;
        }

        $actions = ['connexion', 'creation_document', 'partage_document', 'suppression_document', 'consultation_document'];

        for ($i = 0; $i < 200; $i++) {
            \App\Models\LogAction::create([
                'action' => $actions[array_rand($actions)],
                'table_affectee' => 'documents',
                'user_id' => $users[array_rand($users)],
                'nouvelles_valeurs' => json_encode(['info' => 'Donnée de test']),
                'created_at' => now()->subDays(rand(0, 180)), // Actions sur les 6 derniers mois
                'adresse_ip' => '127.0.0.1',
            ]);
        }

        $this->command->info('200 actions de test ont été ajoutées à la table log_actions.');
    }
}
