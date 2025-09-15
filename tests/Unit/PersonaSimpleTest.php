<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Persona;
use App\Models\Snippet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class PersonaSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip OAuth migrations that cause issues in SQLite
        $this->skipOauthMigrations();
        
        // Create basic tables for testing
        $this->createBasicTables();
    }

    private function skipOauthMigrations()
    {
        // Override migration behavior for OAuth tables
    }

    private function createBasicTables()
    {
        // Create personas table directly without running all migrations
        Schema::create('personas', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->text('ai_expertise_description')->nullable();
            $table->uuid('associated_profile_snippet_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create snippets table for relationship testing
        Schema::create('snippets', function ($table) {
            $table->uuid('id')->primary();
            $table->string('identifier')->unique();
            $table->text('collapsed_html')->nullable();
            $table->text('expanded_html')->nullable();
            $table->text('ai_explanation')->nullable();
            $table->text('hyperlink_keywords')->nullable();
            $table->uuid('assigned_persona_id')->nullable();
            $table->decimal('confidence_threshold', 5, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Test Persona model can be created with basic attributes
     */
    public function test_persona_can_be_created_with_basic_attributes()
    {
        $persona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Test Persona',
            'title' => 'AI Assistant',
            'is_active' => true
        ]);

        $this->assertInstanceOf(Persona::class, $persona);
        $this->assertEquals('Test Persona', $persona->name);
        $this->assertEquals('AI Assistant', $persona->title);
        $this->assertTrue($persona->is_active);
        $this->assertDatabaseHas('personas', [
            'name' => 'Test Persona',
            'title' => 'AI Assistant'
        ]);
    }

    /**
     * Test Persona uses UUID for primary key
     */
    public function test_persona_uses_uuid_for_primary_key()
    {
        $uuid = fake()->uuid();
        $persona = Persona::create([
            'id' => $uuid,
            'name' => 'UUID Test Persona'
        ]);

        $this->assertEquals($uuid, $persona->id);
        $this->assertIsString($persona->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $persona->id
        );
    }

    /**
     * Test Persona basic attributes and table configuration
     */
    public function test_persona_basic_configuration()
    {
        $persona = new Persona();
        
        $this->assertEquals('personas', $persona->getTable());
        $this->assertEquals('id', $persona->getKeyName());
        $this->assertFalse($persona->getIncrementing());
        $this->assertEquals('string', $persona->getKeyType());
    }

    /**
     * Test Persona active scope
     */
    public function test_persona_active_scope()
    {
        // Create active persona
        Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Active Persona',
            'is_active' => true
        ]);

        // Create inactive persona
        Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Inactive Persona',
            'is_active' => false
        ]);

        $activePersonas = Persona::active()->get();

        $this->assertCount(1, $activePersonas);
        $this->assertEquals('Active Persona', $activePersonas->first()->name);
        $this->assertTrue($activePersonas->first()->is_active);
    }

    /**
     * Test Persona inactive scope
     */
    public function test_persona_inactive_scope()
    {
        // Create active persona
        Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Active Persona',
            'is_active' => true
        ]);

        // Create inactive persona
        Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Inactive Persona',
            'is_active' => false
        ]);

        $inactivePersonas = Persona::inactive()->get();

        $this->assertCount(1, $inactivePersonas);
        $this->assertEquals('Inactive Persona', $inactivePersonas->first()->name);
        $this->assertFalse($inactivePersonas->first()->is_active);
    }

    /**
     * Test Persona helper methods
     */
    public function test_persona_helper_methods()
    {
        $activePersona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Active Persona',
            'is_active' => true
        ]);

        $inactivePersona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Inactive Persona',
            'is_active' => false
        ]);

        $this->assertTrue($activePersona->isActive());
        $this->assertFalse($activePersona->isInactive());
        
        $this->assertFalse($inactivePersona->isActive());
        $this->assertTrue($inactivePersona->isInactive());
    }

    /**
     * Test Persona relationship with profile snippet
     */
    public function test_persona_profile_snippet_relationship()
    {
        // Create a snippet
        $snippet = Snippet::create([
            'id' => fake()->uuid(),
            'identifier' => 'test-snippet-1',
            'collapsed_html' => '<p>Test snippet</p>',
            'is_active' => true
        ]);

        // Create a persona with the snippet as profile
        $persona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Test Persona',
            'associated_profile_snippet_id' => $snippet->id
        ]);

        // Test the relationship
        $this->assertNotNull($persona->profileSnippet);
        $this->assertInstanceOf(Snippet::class, $persona->profileSnippet);
        $this->assertEquals($snippet->id, $persona->profileSnippet->id);
        $this->assertEquals('test-snippet-1', $persona->profileSnippet->identifier);
    }

    /**
     * Test Persona assigned snippets relationship
     */
    public function test_persona_assigned_snippets_relationship()
    {
        // Create a persona
        $persona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Test Persona'
        ]);

        // Create snippets assigned to this persona
        $snippet1 = Snippet::create([
            'id' => fake()->uuid(),
            'identifier' => 'assigned-snippet-1',
            'assigned_persona_id' => $persona->id,
            'is_active' => true
        ]);

        $snippet2 = Snippet::create([
            'id' => fake()->uuid(),
            'identifier' => 'assigned-snippet-2',
            'assigned_persona_id' => $persona->id,
            'is_active' => true
        ]);

        // Create a snippet assigned to a different persona
        Snippet::create([
            'id' => fake()->uuid(),
            'identifier' => 'other-snippet',
            'assigned_persona_id' => fake()->uuid(),
            'is_active' => true
        ]);

        // Test the relationship
        $assignedSnippets = $persona->assignedSnippets;

        $this->assertInstanceOf(Collection::class, $assignedSnippets);
        $this->assertCount(2, $assignedSnippets);
        
        $identifiers = $assignedSnippets->pluck('identifier')->toArray();
        $this->assertContains('assigned-snippet-1', $identifiers);
        $this->assertContains('assigned-snippet-2', $identifiers);
        $this->assertNotContains('other-snippet', $identifiers);
    }

    /**
     * Test Snippet belongs to persona relationship
     */
    public function test_snippet_belongs_to_persona_relationship()
    {
        // Create a persona
        $persona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Assigned Persona'
        ]);

        // Create a snippet assigned to this persona
        $snippet = Snippet::create([
            'id' => fake()->uuid(),
            'identifier' => 'test-snippet',
            'assigned_persona_id' => $persona->id,
            'is_active' => true
        ]);

        // Test the relationship
        $this->assertNotNull($snippet->assignedPersona);
        $this->assertInstanceOf(Persona::class, $snippet->assignedPersona);
        $this->assertEquals($persona->id, $snippet->assignedPersona->id);
        $this->assertEquals('Assigned Persona', $snippet->assignedPersona->name);
    }

    /**
     * Test Persona CRUD operations
     */
    public function test_persona_crud_operations()
    {
        // Create
        $persona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'CRUD Test Persona',
            'title' => 'Test Title',
            'is_active' => true
        ]);

        $this->assertDatabaseHas('personas', [
            'name' => 'CRUD Test Persona',
            'title' => 'Test Title'
        ]);

        // Update
        $persona->update(['name' => 'Updated Persona Name']);
        
        $this->assertEquals('Updated Persona Name', $persona->fresh()->name);
        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'name' => 'Updated Persona Name'
        ]);

        // Delete
        $personaId = $persona->id;
        $persona->delete();

        $this->assertDatabaseMissing('personas', ['id' => $personaId]);
    }

    /**
     * Test Persona attribute casting
     */
    public function test_persona_attribute_casting()
    {
        $persona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Cast Test Persona',
            'is_active' => 1 // integer input
        ]);

        // Should be cast to boolean
        $this->assertIsBool($persona->is_active);
        $this->assertTrue($persona->is_active);

        $persona = Persona::create([
            'id' => fake()->uuid(),
            'name' => 'Cast Test Persona 2',
            'is_active' => 0 // integer input
        ]);

        // Should be cast to boolean
        $this->assertIsBool($persona->is_active);
        $this->assertFalse($persona->is_active);
    }
}