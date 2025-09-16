<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use App\Http\Requests\StoreKnowledgeBaseRequest;
use App\Http\Requests\UpdateKnowledgeBaseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PgSql\Lob;
use Illuminate\Support\Facades\Log;

class KnowledgeBaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeBase::query();

        // Search by content
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('content', 'ILIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $knowledgeBases = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $knowledgeBases->items(),
            'meta' => [
                'current_page' => $knowledgeBases->currentPage(),
                'last_page' => $knowledgeBases->lastPage(),
                'per_page' => $knowledgeBases->perPage(),
                'total' => $knowledgeBases->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKnowledgeBaseRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $knowledgeBase = new KnowledgeBase();
            $knowledgeBase->id = (string)Str::uuid();
            $knowledgeBase->content = $validated['content'];
            $knowledgeBase->embedding = $validated['embedding'];
            $knowledgeBase->updated_by = $validated['updated_by'] ?? optional(auth())->id();

            // Get current tenant from the app container
            $tenant = app()->has('currentTenant') ? app('currentTenant') : null;
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tenant found for current domain'
                ], 422);
            }
            $knowledgeBase->tenant_id = $tenant->id;

            $knowledgeBase->save();
            Log::info('Saved KB', $knowledgeBase->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Knowledge base entry created successfully',
                'data' => $knowledgeBase
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create knowledge base entry',
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
            $knowledgeBase = KnowledgeBase::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $knowledgeBase
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve knowledge base entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKnowledgeBaseRequest $request, string $id): JsonResponse
    {
        try {
            $knowledgeBase = KnowledgeBase::findOrFail($id);
            $validated = $request->validated();

            $knowledgeBase->content = $validated['content'];
            $knowledgeBase->embedding = $validated['embedding'];
            // $knowledgeBase->updated_by = $validated['updated_by'] ?? $knowledgeBase->updated_by;
            $knowledgeBase->updated_by = $validated['updated_by'] ?? optional(auth())->id();
            $knowledgeBase->last_updated = now();
            $knowledgeBase->save();

            return response()->json([
                'success' => true,
                'message' => 'Knowledge base entry updated successfully',
                'data' => $knowledgeBase
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Knowledge base entry not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update knowledge base entry',
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
            $knowledgeBase = KnowledgeBase::findOrFail($id);
            $knowledgeBase->delete();

            return response()->json([
                'success' => true,
                'message' => 'Knowledge base entry deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Knowledge base entry not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete knowledge base entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
