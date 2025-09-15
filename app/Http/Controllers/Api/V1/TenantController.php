<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class TenantController extends Controller
{
    /**
     * Register a new tenant for a domain.
     */
    #[OA\Post(
        path: "/tenants",
        summary: "Register new tenant",
        description: "Register a new tenant for a specific domain (for frontend applications)",
        tags: ["Tenants"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "domain"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Acme Corporation"),
                    new OA\Property(property: "domain", type: "string", example: "acme.com"),
                    new OA\Property(property: "settings", type: "object", example: ["theme" => "dark", "features" => ["chat", "ai"]]),
                    new OA\Property(property: "is_active", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Tenant created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tenant created successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer"),
                                new OA\Property(property: "name", type: "string"),
                                new OA\Property(property: "domain", type: "string"),
                                new OA\Property(property: "is_active", type: "boolean"),
                                new OA\Property(property: "settings", type: "object")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Validation error"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:tenants,domain|max:255',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenant = Tenant::create([
            'name' => $request->name,
            'domain' => $request->domain,
            'settings' => $request->settings ?? [],
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully',
            'data' => $tenant
        ], 201);
    }
    
    /**
     * Get current tenant information.
     */
    #[OA\Get(
        path: "/tenant/current",
        summary: "Get current tenant",
        description: "Get information about the current tenant based on domain",
        tags: ["Tenants"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Current tenant information",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer"),
                                new OA\Property(property: "name", type: "string"),
                                new OA\Property(property: "domain", type: "string"),
                                new OA\Property(property: "is_active", type: "boolean"),
                                new OA\Property(property: "settings", type: "object")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "No tenant found for current domain",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "No tenant found for current domain")
                    ]
                )
            )
        ]
    )]
    public function current()
    {
        $tenant = app()->has('currentTenant') ? app('currentTenant') : null;
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant found for current domain'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $tenant
        ]);
    }
}