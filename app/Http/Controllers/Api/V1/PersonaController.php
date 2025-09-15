<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PersonaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Persona::query();

        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or title
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('title', 'ILIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $personas = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $personas->items(),
            'meta' => [
                'current_page' => $personas->currentPage(),
                'last_page' => $personas->lastPage(),
                'per_page' => $personas->perPage(),
                'total' => $personas->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $persona = new Persona();
            $persona->id = Str::uuid();
            $persona->name = $validated['name'];
            $persona->title = $validated['title'] ?? null;
            $persona->profile_picture_url = $validated['profile_picture_url'] ?? null;
            $persona->ai_expertise_description = $validated['ai_expertise_description'] ?? null;
            $persona->associated_profile_snippet_id = $validated['associated_profile_snippet_id'] ?? null;
            $persona->is_active = $validated['is_active'] ?? true;
            $persona->save();

            return response()->json([
                'success' => true,
                'message' => 'Persona created successfully',
                'data' => $persona
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create persona',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $persona = Persona::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $persona
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Persona not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve persona',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonaRequest $request, string $id): JsonResponse
    {
        try {
            $persona = Persona::findOrFail($id);
            $validated = $request->validated();

            $persona->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Persona updated successfully',
                'data' => $persona
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Persona not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update persona',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $persona = Persona::findOrFail($id);
            $persona->delete();

            return response()->json([
                'success' => true,
                'message' => 'Persona deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Persona not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete persona',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
