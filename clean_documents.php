<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

echo "=== DOCUMENTS ACTUELS ===\n\n";

$documents = Document::withTrashed()->get();

echo "Total documents (incluant supprimés): " . $documents->count() . "\n";
echo "Taille totale: " . number_format(Document::sum('taille') / 1024 / 1024, 2) . " MB\n\n";

foreach ($documents as $doc) {
    $status = $doc->trashed() ? '[SUPPRIMÉ]' : '[ACTIF]';
    echo sprintf(
        "%s ID: %d | %s | %.2f KB | User: %d | Créé: %s\n",
        $status,
        $doc->id,
        substr($doc->nom, 0, 50),
        $doc->taille / 1024,
        $doc->user_id,
        $doc->created_at->format('Y-m-d H:i')
    );
}

echo "\n=== OPTIONS ===\n";
echo "1. Supprimer tous les documents de test (créés aujourd'hui)\n";
echo "2. Supprimer TOUS les documents (ATTENTION!)\n";
echo "3. Supprimer uniquement les documents supprimés (soft deleted)\n";
echo "4. Annuler\n\n";

echo "Votre choix (1-4): ";
$choice = trim(fgets(STDIN));

switch ($choice) {
    case '1':
        $today = now()->startOfDay();
        $deleted = Document::where('created_at', '>=', $today)->forceDelete();
        echo "Documents d'aujourd'hui supprimés.\n";
        break;
        
    case '2':
        echo "ATTENTION: Voulez-vous vraiment supprimer TOUS les documents? (oui/non): ";
        $confirm = trim(fgets(STDIN));
        if ($confirm === 'oui') {
            // Supprimer les fichiers physiques
            foreach (Document::withTrashed()->get() as $doc) {
                if (Storage::disk('public')->exists($doc->chemin)) {
                    Storage::disk('public')->delete($doc->chemin);
                }
            }
            Document::withTrashed()->forceDelete();
            echo "Tous les documents supprimés.\n";
        } else {
            echo "Annulé.\n";
        }
        break;
        
    case '3':
        $trashedDocs = Document::onlyTrashed()->get();
        foreach ($trashedDocs as $doc) {
            if (Storage::disk('public')->exists($doc->chemin)) {
                Storage::disk('public')->delete($doc->chemin);
            }
            $doc->forceDelete();
        }
        echo count($trashedDocs) . " documents supprimés définitivement.\n";
        break;
        
    case '4':
    default:
        echo "Annulé.\n";
        break;
}
