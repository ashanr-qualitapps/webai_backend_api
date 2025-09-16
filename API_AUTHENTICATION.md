# WebAI Backend API Authentication

## Overview

This API uses Laravel Passport for OAuth2 authentication with short-lived access tokens and refresh tokens. The system implements **dual-layer authorization**:ckend API Authentication

## Overview

This API uses Laravel Passport for OAuth2 authentication with sh        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "refresh_token": "def502004b5c6d3c7e2f1a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f",
        "token_type": "Bearer",
        "expires_in": 900,
        "scopes": ["read", "write", "super-admin"],
        "user": {ived access tokens and refresh tokens. The system implements **dual-layer authorization**:

1. **OAuth2 Scopes** - Standard OAuth2 scope-based authorization
2. **JSON Permissions** - Fine-grained permission system stored in user profiles

Both systems work together to provide comprehensive access control.

## Multi-Tenancy

The API supports multi-tenancy using app keys. Tenants can be identified by:

1. **App Key** (Recommended) - UUID-based tenant identification
2. **Domain** (Legacy) - Domain-based tenant identification for backward compatibility

### App Key Authentication

Include the tenant's app key in the request header:

```
X-Tenant-Key: 550e8400-e29b-41d4-a716-446655440001
```

### Domain Authentication (Legacy)

The system will fall back to domain-based tenant resolution if no app key is provided:

```
Host: tenant1.webai.com
```

## Authentication Flow

1. **Login** → Get access token + refresh token with appropriate scopes
2. **Use access token** → Make API requests with `Authorization: Bearer <token>`
3. **Token expires (15 min)** → Use refresh token to get new access token
4. **Logout** → Revoke all tokens

## OAuth2 Scopes

The following OAuth2 scopes are available:

### Basic Scopes
- `read` - Read basic data (default scope)
- `write` - Create and update data
- `delete` - Delete data

### Resource-Specific Scopes
- `users:read`, `users:write`, `users:delete` - User management
- `personas:read`, `personas:write`, `personas:delete` - Persona management
- `chat:read`, `chat:write`, `chat:delete` - Chat session management
- `knowledge:read`, `knowledge:write`, `knowledge:delete` - Knowledge base management
- `snippets:read`, `snippets:write`, `snippets:delete` - Snippet management
- `suggestions:read`, `suggestions:write`, `suggestions:delete` - Suggestion management
- `tenants:read`, `tenants:write`, `tenants:delete` - Tenant management

### Administrative Scopes
- `admin` - Administrative access (grants most permissions)
- `super-admin` - Super administrative access (grants all permissions)

## Scope Assignment

Scopes are automatically assigned based on user permissions during login:

| User Permission | Assigned OAuth2 Scopes |
|---|---|
| `*` or `admin.*` | All scopes including `super-admin` |
| `users.read` | `read`, `users:read` |
| `users.create` | `read`, `write`, `users:write` |
| `users.update` | `read`, `write`, `users:write` |
| `users.delete` | `read`, `write`, `delete`, `users:delete` |
| `users.*` | `read`, `write`, `delete`, `users:read`, `users:write`, `users:delete` |
| `personas.read` | `read`, `personas:read` |
| `personas.create` | `read`, `write`, `personas:write` |
| `personas.*` | `read`, `write`, `delete`, `personas:read`, `personas:write`, `personas:delete` |
| `chat.read` | `read`, `chat:read` |
| `chat.*` | `read`, `write`, `delete`, `chat:read`, `chat:write`, `chat:delete` |
| `knowledge.read` | `read`, `knowledge:read` |
| `knowledge.*` | `read`, `write`, `delete`, `knowledge:read`, `knowledge:write`, `knowledge:delete` |
| `snippets.read` | `read`, `snippets:read` |
| `snippets.*` | `read`, `write`, `delete`, `snippets:read`, `snippets:write`, `snippets:delete` |
| `suggestions.read` | `read`, `suggestions:read` |
| `suggestions.*` | `read`, `write`, `delete`, `suggestions:read`, `suggestions:write`, `suggestions:delete` |
| `tenants.read` | `read`, `tenants:read` |
| `tenants.*` | `read`, `write`, `delete`, `tenants:read`, `tenants:write`, `tenants:delete` |

## Endpoints

### Authentication

#### POST /api/v1/register
Register a new admin user

**Request:**
```json
{
    "email": "admin@webai.com",
    "password": "password123",
    "password_confirmation": "password123",
    "full_name": "Admin User",
    "permissions": ["users.read", "users.create"],
    "metadata": {"department": "IT", "role": "Administrator"},
    "is_active": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Admin user registered successfully",
    "data": {
        "user": {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "email": "admin@webai.com",
            "full_name": "Admin User",
            "permissions": ["users.read", "users.create"],
            "is_active": true,
            "created_at": "2025-09-15T10:00:00.000000Z",
            "updated_at": "2025-09-15T10:00:00.000000Z"
        }
    }
}
```

**Required Fields:**
- `email`: Valid email address (must be unique)
- `password`: Minimum 8 characters
- `password_confirmation`: Must match password
- `full_name`: User's full name

**Optional Fields:**
- `permissions`: Array of permission strings (defaults to empty array)
- `metadata`: JSON object for additional user data (defaults to empty object)
- `is_active`: Boolean to set user status (defaults to true)

#### POST /api/v1/login
Login and get access token

**Request:**
```json
{
    "email": "admin@webai.com",
    "password": "password123"
}
```

**Headers (Optional):**
```
X-Tenant-Key: 550e8400-e29b-41d4-a716-446655440001
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "refresh_token": "def502004b5c6d3c7e2f1a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f",
        "token_type": "Bearer",
        "expires_in": 900,
        "scopes": ["read", "write", "super-admin"],
        "user": {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "email": "admin@webai.com",
            "full_name": "Super Admin",
            "permissions": ["*"],
            "is_active": true,
            "last_login": "2025-09-15T10:30:00.000000Z"
        }
    }
}
```

#### POST /api/v1/refresh
Refresh access token using refresh token

**Request:**
```json
{
    "refresh_token": "def502004b5c6d3c7e2f1a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f..."
}
```

**Response:** Same as login response with new tokens

#### POST /api/v1/logout
Logout and revoke all tokens

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response:**
```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

#### GET /api/v1/user
Get current authenticated user

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "email": "admin@webai.com",
            "full_name": "Super Admin",
            "permissions": ["*"],
            "is_active": true,
            "last_login": "2025-09-15T10:30:00.000000Z",
            "created_at": "2025-09-15T10:00:00.000000Z",
            "updated_at": "2025-09-15T10:30:00.000000Z"
        }
    }
}
```

## Permission System

### Permission Format
Permissions are stored as JSON arrays in the `admin_users.permissions` field.

### Permission Types

1. **Wildcard Admin**: `["*"]` - Full system access
2. **Module Wildcard**: `["users.*"]` - All permissions for a module
3. **Specific Permission**: `["users.read", "users.create"]` - Specific actions

### Available Permissions

- `users.*` / `users.read` / `users.create` / `users.update` / `users.delete`
- `knowledge.*` / `knowledge.read` / `knowledge.create` / `knowledge.update` / `knowledge.delete`
- `chat.*` / `chat.read` / `chat.create` / `chat.update` / `chat.delete`
- `personas.*` / `personas.read` / `personas.create` / `personas.update` / `personas.delete`
- `snippets.*` / `snippets.read` / `snippets.create` / `snippets.update` / `snippets.delete`
- `suggestions.*` / `suggestions.read` / `suggestions.create` / `suggestions.update` / `suggestions.delete`
- `admin.*` - System administration permissions

### Test Users

The seeder creates these test users:

1. **Super Admin**
   - Email: `admin@webai.com`
   - Password: `password123`
   - Permissions: `["*"]`

2. **Regular User**
   - Email: `user@webai.com`
   - Password: `password123`
   - Permissions: Read-only access to most modules

3. **Content Manager**
   - Email: `manager@webai.com`
   - Password: `password123`
   - Permissions: Full access to content modules (knowledge, chat, personas, snippets, suggestions)

## Middleware Usage

The system provides multiple middleware options for protecting routes:

### Permission-based Middleware
```php
// Requires specific JSON permission
Route::middleware(['permission:users.read'])->group(function () {
    // Protected routes
});
```

### Scope-based Middleware
```php
// Requires ANY of the specified OAuth2 scopes (OR logic)
Route::middleware(['scope:users:read,read'])->group(function () {
    // User can have either 'users:read' OR 'read' scope
});

// Requires ALL specified OAuth2 scopes (AND logic)
Route::middleware(['scopes:users:read,users:write'])->group(function () {
    // User must have both 'users:read' AND 'users:write' scopes
});
```

### Combined Middleware (Recommended)
```php
// Requires both permission AND scope
Route::middleware(['permission:users.read', 'scope:users:read,read'])->group(function () {
    // User must have both the JSON permission AND the OAuth2 scope
});
```

### Available Middleware

| Middleware | Description | Logic |
|---|---|---|
| `permission:perm` | Check JSON permission | Exact or wildcard match |
| `scope:scope1,scope2` | Check OAuth2 scopes | OR logic (any scope) |
| `scopes:scope1,scope2` | Check OAuth2 scopes | AND logic (all scopes) |
| `tenant` | Resolve tenant from app key/domain | Automatic tenant resolution |

**Note:** The `tenant` middleware is automatically applied to API routes and resolves tenants in the following order:
1. `X-Tenant-Key` header (UUID format)
2. `Host` header domain (legacy support)
3. Default tenant if configured

### Example Route Protection
```php
// Read-only access - requires both permission and scope
Route::get('/users', [UserController::class, 'index'])
    ->middleware(['permission:users.read', 'scope:users:read,read']);

// Write access - requires both permission and scope
Route::post('/users', [UserController::class, 'store'])
    ->middleware(['permission:users.create', 'scope:users:write,write']);

// Admin only - requires super admin scope
Route::delete('/users/{id}', [UserController::class, 'destroy'])
    ->middleware(['permission:users.delete', 'scope:super-admin,admin']);
```

## Error Responses

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

### 403 Forbidden (Insufficient Permissions)
```json
{
    "success": false,
    "message": "Insufficient permissions",
    "required_permission": "users.create"
}
```

### 403 Forbidden (Insufficient Scopes)
```json
{
    "success": false,
    "message": "Insufficient scope",
    "required_scopes": ["users:write", "write"],
    "token_scopes": ["read", "users:read"]
}
```

### 422 Validation Error
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

## Usage Examples

### cURL Examples

**Login:**
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: 550e8400-e29b-41d4-a716-446655440001" \
  -d '{"email":"admin@webai.com","password":"password123"}'
```

**Make Authenticated Request:**
```bash
curl -X GET http://localhost:8000/api/v1/user \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "X-Tenant-Key: 550e8400-e29b-41d4-a716-446655440001"
```

**Refresh Token:**
```bash
curl -X POST http://localhost:8000/api/v1/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token":"def502004b5c6d3c7e2f1a8b9c0d1e2f..."}'
```

**Logout:**
```bash
curl -X POST http://localhost:8000/api/v1/logout \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
```

## Security Notes

- Access tokens expire in 15 minutes
- Refresh tokens expire in 30 days
- All tokens are revoked on logout
- Inactive users cannot authenticate
- Permission checks are performed on every protected route
- Multi-tenancy isolation ensures data separation between tenants
- App keys are UUID v4 format for enhanced security
- Scope validation includes super-admin bypass for administrative operations
- All authentication requests should include appropriate tenant identification