<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentForceDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_delete_removes_file_and_db_row(): void
    {
        // Arrange
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $uuid = (string) Str::uuid();
        $path = 'documents/sample.pdf';

        // Create a fake file in storage
        Storage::disk('public')->put($path, 'dummy');

        // Create document and soft-delete it
        $doc = Document::create([
            'uuid' => $uuid,
            'nom' => 'sample.pdf',
            'chemin' => $path,
            'type' => 'application/pdf',
            'taille' => 10,
            'user_id' => $user->id,
        ]);
        $doc->delete();

        // Pre-assertions
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertDatabaseHas('documents', ['uuid' => $uuid]);

        // Act
        $response = $this->deleteJson("/api/documents/{$uuid}/force");

        // Assert
        $response->assertStatus(200)
            ->assertJson(['message' => 'Document supprimé définitivement.']);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('documents', ['uuid' => $uuid]);
    }
}
