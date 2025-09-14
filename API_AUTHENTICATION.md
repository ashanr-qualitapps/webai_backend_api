# WebAI Backend API Authentication

## Overview

This API uses Laravel Passport for OAuth2 authentication with short-lived access tokens and refresh tokens. The permission system is based on JSON permissions stored in the `admin_users.permissions` field.

## Authentication Flow

1. **Login** → Get access token + refresh token
2. **Use access token** → Make API requests with `Authorization: Bearer <token>`
3. **Token expires (15 min)** → Use refresh token to get new access token
4. **Logout** → Revoke all tokens

## Endpoints

### Authentication

#### POST /api/v1/login
Login and get access token

**Request:**
```json
{
    "email": "admin@webai.com",
    "password": "password123"
}
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
  -d '{"email":"admin@webai.com","password":"password123"}'
```

**Make Authenticated Request:**
```bash
curl -X GET http://localhost:8000/api/v1/user \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
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