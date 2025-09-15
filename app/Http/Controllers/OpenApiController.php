<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "WebAI Backend API",
    description: "Multi-tenant API for WebAI application with authentication, user management, and AI features"
)]
#[OA\Server(
    url: "http://localhost/api/v1",
    description: "Local development server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Tag(
    name: "Authentication",
    description: "Authentication endpoints for admin users"
)]
#[OA\Tag(
    name: "Users",
    description: "Normal user management endpoints"
)]
class OpenApiController extends Controller
{
    // This controller is just for OpenAPI documentation
}