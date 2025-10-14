<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentsTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_trash_lists_only_soft_deleted_documents_for_authenticated_user(): void
    {
        // Arrange: create user and two documents (one active, one soft-deleted)
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $docActive = Document::create([
            'uuid' => (string) Str::uuid(),
            'nom' => 'active.pdf',
            'chemin' => 'documents/active.pdf',
            'type' => 'application/pdf',
            'taille' => 123,
            'user_id' => $user->id,
        ]);

        $docTrashed = Document::create([
            'uuid' => (string) Str::uuid(),
            'nom' => 'trashed.pdf',
            'chemin' => 'documents/trashed.pdf',
            'type' => 'application/pdf',
            'taille' => 456,
            'user_id' => $user->id,
        ]);
        // Soft delete one
        $docTrashed->delete();

        // Act: call trash endpoint
        $response = $this->getJson('/api/documents/trash');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data', 'path', 'per_page'
        ]);

        $payload = $response->json();
        $names = collect($payload['data'])->pluck('nom');

        $this->assertTrue($names->contains('trashed.pdf'));
        $this->assertFalse($names->contains('active.pdf'));
    }
}
