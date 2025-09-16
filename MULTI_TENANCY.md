# Multi-Tenancy Implementation with App Keys

## Overview

This Laravel application now implements **app key-based multi-tenancy** where each tenant is identified by a unique UUID (app_key) instead of domain-based identification. This approach provides better security, flexibility, and scalability for API-based applications.

## Key Features

- **UUID-based Tenant Identification**: Each tenant gets a unique UUID as their `app_key`
- **Header-based Resolution**: Tenants are resolved via HTTP headers
- **Automatic Tenant Scoping**: All tenant-scoped models automatically filter by the current tenant
- **Backward Compatibility**: Domain-based resolution is still supported as a fallback

## Implementation Details

### 1. Database Schema

**Tenants Table Structure:**
```sql
- id (bigint, primary key)
- name (string)
- app_key (uuid, unique, not null) -- Primary tenant identifier
- domain (string, nullable) -- Optional, for backward compatibility
- is_active (boolean)
- settings (json)
- created_at, updated_at
```

### 2. Tenant Resolution Methods

The `ResolveTenant` middleware resolves tenants in the following priority order:

1. **X-App-Key Header** (Primary method)
2. **X-Tenant-Key Header** (Alternative header)
3. **Authenticated User's Tenant** (From token/user relationship)
4. **Domain-based Methods** (Legacy fallback):
   - Full domain matching
   - Subdomain matching
   - Origin header
   - X-Tenant-Domain header

### 3. API Usage

#### Creating a New Tenant

**Endpoint:** `POST /api/v1/tenants`

**Request:**
```json
{
    "name": "My Company",
    "domain": "mycompany.com", // Optional
    "settings": {
        "theme": "dark",
        "features": ["chat", "ai"]
    },
    "is_active": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Tenant created successfully",
    "data": {
        "id": 1,
        "name": "My Company",
        "app_key": "550e8400-e29b-41d4-a716-446655440001",
        "domain": "mycompany.com",
        "is_active": true,
        "settings": {
            "theme": "dark",
            "features": ["chat", "ai"]
        },
        "created_at": "2025-09-16T10:00:00.000000Z",
        "updated_at": "2025-09-16T10:00:00.000000Z"
    }
}
```

#### Making API Requests with App Key

All API requests should include the tenant's app_key in the headers:

**Method 1: Using X-App-Key Header**
```http
GET /api/v1/tenant/current
Authorization: Bearer your_auth_token
X-App-Key: 550e8400-e29b-41d4-a716-446655440001
Content-Type: application/json
```

**Method 2: Using X-Tenant-Key Header**
```http
GET /api/v1/personas
Authorization: Bearer your_auth_token
X-Tenant-Key: 550e8400-e29b-41d4-a716-446655440001
Content-Type: application/json
```

#### Getting Current Tenant Information

**Endpoint:** `GET /api/v1/tenant/current`

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "My Company",
        "app_key": "550e8400-e29b-41d4-a716-446655440001",
        "domain": "mycompany.com",
        "is_active": true,
        "settings": {
            "theme": "dark",
            "features": ["chat", "ai"]
        },
        "created_at": "2025-09-16T10:00:00.000000Z",
        "updated_at": "2025-09-16T10:00:00.000000Z"
    }
}
```

### 4. Development & Testing

#### Sample App Keys for Testing

The seeder creates the following test tenants:

1. **Acme Corporation**: `550e8400-e29b-41d4-a716-446655440001`
2. **TechStart Inc**: `550e8400-e29b-41d4-a716-446655440002`
3. **Global Solutions**: `550e8400-e29b-41d4-a716-446655440003`
4. **Demo Company**: `550e8400-e29b-41d4-a716-446655440004`
5. **Development Environment**: `550e8400-e29b-41d4-a716-446655440005`

#### Testing with cURL

```bash
# Create a new tenant
curl -X POST http://localhost/api/v1/tenants \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Company",
    "settings": {"theme": "light"}
  }'

# Get current tenant info using app_key
curl -X GET http://localhost/api/v1/tenant/current \
  -H "X-App-Key: 550e8400-e29b-41d4-a716-446655440001" \
  -H "Authorization: Bearer your_token"

# Get personas for a specific tenant
curl -X GET http://localhost/api/v1/personas \
  -H "X-App-Key: 550e8400-e29b-41d4-a716-446655440001" \
  -H "Authorization: Bearer your_token"
```

### 5. Migration & Deployment

#### Running the Migration

```bash
# Run the migration to add app_key to existing tenants
docker-compose exec app php artisan migrate

# Seed the database with sample tenants
docker-compose exec app php artisan db:seed --class=TenantSeeder
```

#### Upgrading Existing Systems

If you have an existing system with domain-based tenancy:

1. Run the migration to add `app_key` to existing tenants
2. Update your client applications to include `X-App-Key` header
3. Gradually phase out domain-based resolution
4. The system maintains backward compatibility during transition

### 6. Security Considerations

- **App Keys are UUIDs**: Difficult to guess or enumerate
- **Header-based**: More secure than URL parameters
- **Token Validation**: Authenticated requests can resolve tenant from user relationships
- **Active Tenant Check**: Only active tenants are resolved

### 7. Model Integration

All tenant-scoped models automatically filter by the current tenant:

```php
// These will automatically be scoped to the current tenant
$personas = Persona::all();
$chatSessions = ChatSession::all();
$snippets = Snippet::all();
$knowledgeBase = KnowledgeBase::all();
```

The global scope is applied automatically via the `booted()` method in each model:

```php
protected static function booted()
{
    static::addGlobalScope('tenant', function ($query) {
        if (app()->has('currentTenant')) {
            $tenantId = app('currentTenant')->id;
            $query->where('tenant_id', $tenantId);
        }
    });
}
```

### 8. Error Handling

If no tenant is resolved:
- Non-tenant-specific endpoints work normally
- Tenant-scoped endpoints return empty results
- Current tenant endpoint returns 404

### 9. Frontend Integration

#### JavaScript/React Example

```javascript
// Store the app_key after tenant creation or login
const appKey = '550e8400-e29b-41d4-a716-446655440001';

// Include in all API requests
const apiCall = async (endpoint, options = {}) => {
    const response = await fetch(`/api/v1${endpoint}`, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`,
            'X-App-Key': appKey,
            ...options.headers
        }
    });
    return response.json();
};

// Usage
const currentTenant = await apiCall('/tenant/current');
const personas = await apiCall('/personas');
```

This implementation provides a robust, secure, and scalable multi-tenancy solution that can be easily integrated into any client application while maintaining backward compatibility with existing domain-based systems.