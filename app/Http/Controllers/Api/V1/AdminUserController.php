<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminUserController extends Controller
{
    /**
     * Delete an admin user for the current tenant.
     */
    #[OA\Delete(
        path: "/admin-users/{id}",
        summary: "Delete admin user",
        description: "Delete an admin user that has access to the current tenant",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Admin User ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Admin user deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Admin user deleted successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Admin user not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Admin user not found or does not belong to this tenant")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
                    ]
                )
            )
        ]
    )]
    public function destroy($id)
    {
        $tenant = app()->has('currentTenant') ? app('currentTenant') : null;
        
        // Find admin user that has access to current tenant
        $adminUser = AdminUser::where('id', $id)
            ->whereHas('tenants', function ($query) use ($tenant) {
                if ($tenant) {
                    $query->where('tenants.id', $tenant->id);
                }
            })
            ->first();
            
        if (!$adminUser) {
            return response()->json([
                'success' => false,
                'message' => 'Admin user not found or does not belong to this tenant',
            ], 404);
        }
        
        // If admin user only belongs to current tenant, delete the user
        // If they belong to multiple tenants, just remove the tenant association
        if ($adminUser->tenants()->count() <= 1) {
            $adminUser->delete();
            $message = 'Admin user deleted successfully';
        } else {
            $adminUser->tenants()->detach($tenant->id);
            $message = 'Admin user access to this tenant removed successfully';
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}