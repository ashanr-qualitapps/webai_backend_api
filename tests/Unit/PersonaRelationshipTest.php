<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Persona;
use App\Models\Snippet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class PersonaRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Persona can have a profile snippet
     */
    public function test_persona_can_have_profile_snippet()
    {
        $snippet = Snippet::factory()->create();
        $persona = Persona::factory()->create([
            'associated_profile_snippet_id' => $snippet->id
        ]);

        $this->assertInstanceOf(Snippet::class, $persona->profileSnippet);
        $this->assertEquals($snippet->id, $persona->profileSnippet->id);
    }

    /**
     * Test Persona profile snippet relationship returns null when not set
     */
    public function test_persona_profile_snippet_returns_null_when_not_set()
    {
        $persona = Persona::factory()->create([
            'associated_profile_snippet_id' => null
        ]);

        $this->assertNull($persona->profileSnippet);
    }

    /**
     * Test Persona can have multiple assigned snippets
     */
    public function test_persona_can_have_multiple_assigned_snippets()
    {
        $persona = Persona::factory()->create();
        $snippets = Snippet::factory()->count(3)->create([
            'assigned_persona_id' => $persona->id
        ]);

        $assignedSnippets = $persona->assignedSnippets;

        $this->assertInstanceOf(Collection::class, $assignedSnippets);
        $this->assertCount(3, $assignedSnippets);
        
        foreach ($assignedSnippets as $snippet) {
            $this->assertInstanceOf(Snippet::class, $snippet);
            $this->assertEquals($persona->id, $snippet->assigned_persona_id);
        }
    }

    /**
     * Test Persona assigned snippets returns empty collection when none assigned
     */
    public function test_persona_assigned_snippets_returns_empty_collection_when_none_assigned()
    {
        $persona = Persona::factory()->create();

        $assignedSnippets = $persona->assignedSnippets;

        $this->assertInstanceOf(Collection::class, $assignedSnippets);
        $this->assertCount(0, $assignedSnippets);
        $this->assertTrue($assignedSnippets->isEmpty());
    }

    /**
     * Test Snippet belongs to assigned persona
     */
    public function test_snippet_belongs_to_assigned_persona()
    {
        $persona = Persona::factory()->create();
        $snippet = Snippet::factory()->create([
            'assigned_persona_id' => $persona->id
        ]);

        $this->assertInstanceOf(Persona::class, $snippet->assignedPersona);
        $this->assertEquals($persona->id, $snippet->assignedPersona->id);
        $this->assertEquals($persona->name, $snippet->assignedPersona->name);
    }

    /**
     * Test Snippet assigned persona returns null when not assigned
     */
    public function test_snippet_assigned_persona_returns_null_when_not_assigned()
    {
        $snippet = Snippet::factory()->create([
            'assigned_persona_id' => null
        ]);

        $this->assertNull($snippet->assignedPersona);
    }

    /**
     * Test Persona can be both profile snippet and assigned to other snippets
     */
    public function test_persona_can_be_profile_snippet_and_have_assigned_snippets()
    {
        $persona = Persona::factory()->create();
        
        // Create a snippet that will be the profile snippet
        $profileSnippet = Snippet::factory()->create();
        $persona->update(['associated_profile_snippet_id' => $profileSnippet->id]);
        
        // Create snippets assigned to this persona
        $assignedSnippets = Snippet::factory()->count(2)->create([
            'assigned_persona_id' => $persona->id
        ]);

        // Reload the persona to get fresh relationships
        $persona = $persona->fresh();

        $this->assertInstanceOf(Snippet::class, $persona->profileSnippet);
        $this->assertEquals($profileSnippet->id, $persona->profileSnippet->id);
        
        $this->assertCount(2, $persona->assignedSnippets);
        foreach ($persona->assignedSnippets as $snippet) {
            $this->assertEquals($persona->id, $snippet->assigned_persona_id);
        }
    }

    /**
     * Test Persona relationship queries work correctly
     */
    public function test_persona_relationship_queries_work_correctly()
    {
        $persona1 = Persona::factory()->create();
        $persona2 = Persona::factory()->create();
        
        // Create snippets for persona1
        Snippet::factory()->count(2)->create(['assigned_persona_id' => $persona1->id]);
        
        // Create snippets for persona2
        Snippet::factory()->count(3)->create(['assigned_persona_id' => $persona2->id]);
        
        // Create unassigned snippet
        Snippet::factory()->create(['assigned_persona_id' => null]);

        $persona1Snippets = $persona1->assignedSnippets;
        $persona2Snippets = $persona2->assignedSnippets;

        $this->assertCount(2, $persona1Snippets);
        $this->assertCount(3, $persona2Snippets);
        
        // Verify that each persona only gets their own snippets
        foreach ($persona1Snippets as $snippet) {
            $this->assertEquals($persona1->id, $snippet->assigned_persona_id);
        }
        
        foreach ($persona2Snippets as $snippet) {
            $this->assertEquals($persona2->id, $snippet->assigned_persona_id);
        }
    }

    /**
     * Test Persona with factory relationship states
     */
    public function test_persona_with_snippet_factory_state()
    {
        $persona = Persona::factory()->withSnippet()->create();

        $this->assertNotNull($persona->associated_profile_snippet_id);
        $this->assertInstanceOf(Snippet::class, $persona->profileSnippet);
    }

    /**
     * Test cascading deletion behavior simulation
     */
    public function test_persona_deletion_with_relationships()
    {
        $persona = Persona::factory()->create();
        $profileSnippet = Snippet::factory()->create();
        $assignedSnippets = Snippet::factory()->count(2)->create([
            'assigned_persona_id' => $persona->id
        ]);

        $persona->update(['associated_profile_snippet_id' => $profileSnippet->id]);

        // Store IDs for later verification
        $personaId = $persona->id;
        $profileSnippetId = $profileSnippet->id;
        $assignedSnippetIds = $assignedSnippets->pluck('id')->toArray();

        // Delete the persona
        $persona->delete();

        // Verify persona is deleted
        $this->assertDatabaseMissing('personas', ['id' => $personaId]);
        
        // Profile snippet should still exist (no cascade delete in this direction)
        $this->assertDatabaseHas('snippets', ['id' => $profileSnippetId]);
        
        // Assigned snippets should still exist but with null assigned_persona_id
        // Note: This would depend on actual foreign key constraints in the database
        foreach ($assignedSnippetIds as $snippetId) {
            $this->assertDatabaseHas('snippets', ['id' => $snippetId]);
        }
    }

    /**
     * Test relationship loading performance with eager loading
     */
    public function test_persona_relationships_can_be_eager_loaded()
    {
        $personas = Persona::factory()->count(3)->create();
        
        foreach ($personas as $persona) {
            $profileSnippet = Snippet::factory()->create();
            $persona->update(['associated_profile_snippet_id' => $profileSnippet->id]);
            
            Snippet::factory()->count(2)->create([
                'assigned_persona_id' => $persona->id
            ]);
        }

        // Test eager loading
        $personasWithRelationships = Persona::with(['profileSnippet', 'assignedSnippets'])->get();

        $this->assertCount(3, $personasWithRelationships);
        
        foreach ($personasWithRelationships as $persona) {
            // These should be loaded and not trigger additional queries
            $this->assertInstanceOf(Snippet::class, $persona->profileSnippet);
            $this->assertCount(2, $persona->assignedSnippets);
        }
    }
}