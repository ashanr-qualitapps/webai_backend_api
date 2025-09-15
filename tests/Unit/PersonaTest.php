<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Persona;
use App\Models\Snippet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class PersonaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Persona model can be created with factory
     */
    public function test_persona_can_be_created_with_factory()
    {
        $persona = Persona::factory()->create();

        $this->assertInstanceOf(Persona::class, $persona);
        $this->assertNotEmpty($persona->id);
        $this->assertNotEmpty($persona->name);
        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'name' => $persona->name
        ]);
    }

    /**
     * Test Persona uses UUID for primary key
     */
    public function test_persona_uses_uuid_for_primary_key()
    {
        $persona = Persona::factory()->create();

        $this->assertIsString($persona->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $persona->id
        );
    }

    /**
     * Test Persona has correct table name
     */
    public function test_persona_has_correct_table_name()
    {
        $persona = new Persona();
        
        $this->assertEquals('personas', $persona->getTable());
    }

    /**
     * Test Persona has correct primary key configuration
     */
    public function test_persona_has_correct_primary_key_configuration()
    {
        $persona = new Persona();
        
        $this->assertEquals('id', $persona->getKeyName());
        $this->assertFalse($persona->getIncrementing());
        $this->assertEquals('string', $persona->getKeyType());
    }

    /**
     * Test Persona has mass assignable attributes
     */
    public function test_persona_has_mass_assignable_attributes()
    {
        $attributes = [
            'id' => fake()->uuid(),
            'name' => 'Test Persona',
            'title' => 'AI Assistant',
            'profile_picture_url' => 'https://example.com/image.jpg',
            'ai_expertise_description' => 'Expert in testing',
            'associated_profile_snippet_id' => fake()->uuid(),
            'is_active' => true
        ];

        $persona = Persona::create($attributes);

        $this->assertEquals($attributes['name'], $persona->name);
        $this->assertEquals($attributes['title'], $persona->title);
        $this->assertEquals($attributes['profile_picture_url'], $persona->profile_picture_url);
        $this->assertEquals($attributes['ai_expertise_description'], $persona->ai_expertise_description);
        $this->assertEquals($attributes['associated_profile_snippet_id'], $persona->associated_profile_snippet_id);
        $this->assertEquals($attributes['is_active'], $persona->is_active);
    }

    /**
     * Test Persona can be created with minimal data
     */
    public function test_persona_can_be_created_with_minimal_data()
    {
        $persona = Persona::factory()->minimal()->create([
            'name' => 'Minimal Persona'
        ]);

        $this->assertEquals('Minimal Persona', $persona->name);
        $this->assertNull($persona->title);
        $this->assertNull($persona->profile_picture_url);
        $this->assertNull($persona->ai_expertise_description);
        $this->assertNull($persona->associated_profile_snippet_id);
    }

    /**
     * Test Persona active factory state
     */
    public function test_persona_active_factory_state()
    {
        $persona = Persona::factory()->active()->create();

        $this->assertTrue($persona->is_active);
    }

    /**
     * Test Persona inactive factory state
     */
    public function test_persona_inactive_factory_state()
    {
        $persona = Persona::factory()->inactive()->create();

        $this->assertFalse($persona->is_active);
    }

    /**
     * Test Persona without profile picture factory state
     */
    public function test_persona_without_profile_picture_factory_state()
    {
        $persona = Persona::factory()->withoutProfilePicture()->create();

        $this->assertNull($persona->profile_picture_url);
    }

    /**
     * Test Persona can be updated
     */
    public function test_persona_can_be_updated()
    {
        $persona = Persona::factory()->create();
        $originalName = $persona->name;

        $persona->update(['name' => 'Updated Persona Name']);

        $this->assertEquals('Updated Persona Name', $persona->fresh()->name);
        $this->assertNotEquals($originalName, $persona->fresh()->name);
    }

    /**
     * Test Persona can be deleted
     */
    public function test_persona_can_be_deleted()
    {
        $persona = Persona::factory()->create();
        $personaId = $persona->id;

        $this->assertDatabaseHas('personas', ['id' => $personaId]);

        $persona->delete();

        $this->assertDatabaseMissing('personas', ['id' => $personaId]);
    }

    /**
     * Test Persona has timestamps
     */
    public function test_persona_has_timestamps()
    {
        $persona = Persona::factory()->create();

        $this->assertNotNull($persona->created_at);
        $this->assertNotNull($persona->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $persona->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $persona->updated_at);
    }

    /**
     * Test Persona name is required
     */
    public function test_persona_name_is_required()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Persona::create([
            'id' => fake()->uuid(),
            'title' => 'Test Title'
        ]);
    }

    /**
     * Test Persona can have long expertise description
     */
    public function test_persona_can_have_long_expertise_description()
    {
        $longDescription = str_repeat('This is a very long expertise description. ', 100);
        
        $persona = Persona::factory()->create([
            'ai_expertise_description' => $longDescription
        ]);

        $this->assertEquals($longDescription, $persona->ai_expertise_description);
    }

    /**
     * Test Persona can be filtered by active status
     */
    public function test_persona_can_be_filtered_by_active_status()
    {
        // Create active and inactive personas
        Persona::factory()->active()->count(3)->create();
        Persona::factory()->inactive()->count(2)->create();

        $activePersonas = Persona::where('is_active', true)->get();
        $inactivePersonas = Persona::where('is_active', false)->get();

        $this->assertCount(3, $activePersonas);
        $this->assertCount(2, $inactivePersonas);
        
        foreach ($activePersonas as $persona) {
            $this->assertTrue($persona->is_active);
        }
        
        foreach ($inactivePersonas as $persona) {
            $this->assertFalse($persona->is_active);
        }
    }

    /**
     * Test Persona collection methods
     */
    public function test_persona_collection_methods()
    {
        $personas = Persona::factory()->count(5)->create();

        $this->assertInstanceOf(Collection::class, $personas);
        $this->assertCount(5, $personas);
        
        $firstPersona = $personas->first();
        $this->assertInstanceOf(Persona::class, $firstPersona);
    }

    /**
     * Test Persona to array conversion
     */
    public function test_persona_to_array_conversion()
    {
        $persona = Persona::factory()->create([
            'name' => 'Test Persona',
            'title' => 'AI Assistant'
        ]);

        $array = $persona->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        
        $this->assertEquals('Test Persona', $array['name']);
        $this->assertEquals('AI Assistant', $array['title']);
    }

    /**
     * Test Persona JSON serialization
     */
    public function test_persona_json_serialization()
    {
        $persona = Persona::factory()->create([
            'name' => 'JSON Test Persona'
        ]);

        $json = $persona->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals('JSON Test Persona', $decoded['name']);
        $this->assertArrayHasKey('id', $decoded);
    }

    /**
     * Test multiple Persona creation
     */
    public function test_multiple_persona_creation()
    {
        $count = 10;
        $personas = Persona::factory()->count($count)->create();

        $this->assertCount($count, $personas);
        $this->assertEquals($count, Persona::count());
        
        // Ensure all have unique IDs
        $ids = $personas->pluck('id')->toArray();
        $uniqueIds = array_unique($ids);
        $this->assertCount($count, $uniqueIds);
    }

    /**
     * Test Persona active scope
     */
    public function test_persona_active_scope()
    {
        Persona::factory()->active()->count(3)->create();
        Persona::factory()->inactive()->count(2)->create();

        $activePersonas = Persona::active()->get();

        $this->assertCount(3, $activePersonas);
        foreach ($activePersonas as $persona) {
            $this->assertTrue($persona->is_active);
        }
    }

    /**
     * Test Persona inactive scope
     */
    public function test_persona_inactive_scope()
    {
        Persona::factory()->active()->count(3)->create();
        Persona::factory()->inactive()->count(2)->create();

        $inactivePersonas = Persona::inactive()->get();

        $this->assertCount(2, $inactivePersonas);
        foreach ($inactivePersonas as $persona) {
            $this->assertFalse($persona->is_active);
        }
    }

    /**
     * Test Persona isActive method
     */
    public function test_persona_is_active_method()
    {
        $activePersona = Persona::factory()->active()->create();
        $inactivePersona = Persona::factory()->inactive()->create();

        $this->assertTrue($activePersona->isActive());
        $this->assertFalse($inactivePersona->isActive());
    }

    /**
     * Test Persona isInactive method
     */
    public function test_persona_is_inactive_method()
    {
        $activePersona = Persona::factory()->active()->create();
        $inactivePersona = Persona::factory()->inactive()->create();

        $this->assertFalse($activePersona->isInactive());
        $this->assertTrue($inactivePersona->isInactive());
    }

    /**
     * Test Persona attribute casting
     */
    public function test_persona_attribute_casting()
    {
        $persona = Persona::factory()->create([
            'is_active' => 1 // Integer input
        ]);

        // Should be cast to boolean
        $this->assertIsBool($persona->is_active);
        $this->assertTrue($persona->is_active);

        $persona = Persona::factory()->create([
            'is_active' => 0 // Integer input
        ]);

        // Should be cast to boolean
        $this->assertIsBool($persona->is_active);
        $this->assertFalse($persona->is_active);
    }
}