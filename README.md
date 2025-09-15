# WebAI Backend API

A powerful Laravel-based REST API backend for AI-powered web applications, featuring PostgreSQL database integration and containerized development environment.


## 🚀 About This Project

WebAI Backend API is a robust, scalable backend service built with Laravel's elegant architecture. It provides RESTful endpoints for user management, persona management, and content organization with enterprise-grade security and performance optimization.

### Key Features

- 🏗️ **Extensible Architecture** - Clean structure ready for feature expansion
- 🔐 **Secure Authentication** - JWT-based API authentication with role-based permissions
- 🐘 **PostgreSQL Database** - Advanced relational database with optimized schema
- 🐳 **Docker Support** - Complete containerized development and deployment
- 📊 **Real-time Processing** - Queue-based background job processing
- 🔍 **API Documentation** - Interactive Swagger/OpenAPI documentation
- ⚡ **Performance Optimized** - Redis caching and database query optimization
- 🧪 **Test Coverage** - Comprehensive PHPUnit testing suite


## 🛠️ Technology Stack

- **Backend Framework:** Laravel 12.x
- **Language:** PHP 8.2+
- **Database:** PostgreSQL 15+
- **Caching:** Redis
- **Queue System:** Laravel Queues with Redis driver
- **Authentication:** JWT (JSON Web Tokens)
- **Containerization:** Docker \& Docker Compose
- **Testing:** PHPUnit with Feature \& Unit tests
- **Documentation:** Swagger/OpenAPI 3.0


## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- [Docker](https://docs.docker.com/get-docker/) (v20.0+)
- [Docker Compose](https://docs.docker.com/compose/install/) (v2.0+)
- [Git](https://git-scm.com/)


## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/ashanr-qualitapps/webai_backend_api.git
cd webai_backend_api
```

### 2. Environment Setup

Choose one of the following environment configurations:

#### Option A: Docker Development (Recommended)
```bash
# Copy Docker-specific environment file
cp .env.docker .env

# Note: .env.docker is pre-configured for Docker containers
# Database connection points to 'db' service, not localhost
```

#### Option B: Local Development
```bash
# Copy example environment file for local development
cp .env.example .env

# You'll need to configure database connection manually
# Update DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
```

### 3. Build and Start with Docker (Recommended)

```bash
# Build and start all services in detached mode
docker-compose up --build -d

# Verify containers are running
docker-compose ps
```

Expected output should show:
- `webai-app` (Laravel application)
- `webai-webserver` (Nginx)
- `webai-postgres` (PostgreSQL database)

### 4. Application Initialization

```bash
# Install PHP dependencies
docker-compose exec app composer install

# Generate application key (if not already set)
docker-compose exec app php artisan key:generate

# Run database migrations
docker-compose exec app php artisan migrate

# Seed database with initial data (optional)
docker-compose exec app php artisan db:seed

# Install and build frontend assets (if needed)
docker-compose exec app npm install
docker-compose exec app npm run build
```

### 5. Verify Installation

- **API Base URL:** http://localhost:8000
- **Test endpoint:** http://localhost:8000/api/v1/personas
- **API Documentation:** http://localhost:8000/api/documentation (if implemented)


## 🔧 Development

### Container Services

| Service | URL | Container Name | Purpose |
| :-- | :-- | :-- | :-- |
| Laravel App | http://localhost:8000 | `webai-app` | Main PHP application |
| PostgreSQL | localhost:5432 | `webai-postgres` | Database server |
| Nginx | http://localhost:8000 | `webai-webserver` | Web server |

### Database Connection Details



**For External Access:**
- **Host:** `localhost`
- **Port:** `5432`
- **Database:** `webai_db` 
- **Username:** `webai_user`
- **Password:** `webai_password`

### Common Development Commands

#### Container Management
```bash
# Start all containers
docker-compose up -d

# Stop all containers
docker-compose down

# Rebuild containers (after Dockerfile changes)
docker-compose up --build -d

# View container logs
docker-compose logs -f app
docker-compose logs -f db

# Access application container shell
docker-compose exec app bash

# Restart specific service
docker-compose restart app
```

#### Laravel Commands
```bash
# Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan migrate:rollback
docker-compose exec app php artisan migrate:fresh --seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:list

# Composer commands
docker-compose exec app composer install
docker-compose exec app composer update
docker-compose exec app composer dump-autoload

# Generate new migration
docker-compose exec app php artisan make:migration create_example_table

# Generate new controller
docker-compose exec app php artisan make:controller Api/V1/ExampleController

# Generate new model
docker-compose exec app php artisan make:model Example -m
```

#### Frontend Development
```bash
# Install Node.js dependencies
docker-compose exec app npm install

# Development mode (watch for changes)
docker-compose exec app npm run dev

# Production build
docker-compose exec app npm run build
```


### Database Management

#### Migration Commands
```bash
# Run fresh migrations with seeding
docker-compose exec app php artisan migrate:fresh --seed

# Create new migration
docker-compose exec app php artisan make:migration create_example_table

# Check migration status
docker-compose exec app php artisan migrate:status

# Rollback last migration
docker-compose exec app php artisan migrate:rollback

# Rollback specific number of migrations
docker-compose exec app php artisan migrate:rollback --step=3
```

#### Database Access
```bash
# Access PostgreSQL directly (using docker-compose credentials)
docker-compose exec db psql -U webai_user -d webai_db

# Alternative: using .env.docker credentials
docker-compose exec db psql -U laravel -d laravel

# Backup database
docker-compose exec db pg_dump -U webai_user webai_db > backup.sql

# Restore database
docker-compose exec -T db psql -U webai_user -d webai_db < backup.sql
```

#### Seeding
```bash
# Run all seeders
docker-compose exec app php artisan db:seed

# Run specific seeder
docker-compose exec app php artisan db:seed --class=AdminUserSeeder

# Create new seeder
docker-compose exec app php artisan make:seeder ExampleSeeder
```

### Troubleshooting

#### Common Issues

**1. Port Already in Use**
```bash
# Check what's using port 8000
netstat -tulpn | grep 8000

# Kill process or change port in docker-compose.yml
```

**2. Database Connection Failed**
```bash
# Check if database container is running
docker-compose ps

# Check database logs
docker-compose logs db

# Restart database container
docker-compose restart db
```

**3. Permission Issues**
```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

**4. Clear All Caches**
```bash
docker-compose exec app php artisan optimize:clear
```

#### Environment Issues
```bash
# Regenerate application key
docker-compose exec app php artisan key:generate

# Check environment configuration
docker-compose exec app php artisan config:show

# Validate environment file
docker-compose exec app php artisan config:cache
```


## 📚 API Documentation

### Authentication

The API uses JWT tokens for authentication. Include the token in your requests:

```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -H "Content-Type: application/json" \
     http://localhost:9000/api/protected-endpoint
```


### Core Endpoints

| Method | Endpoint | Description | Auth Required |
| :-- | :-- | :-- | :-- |
| POST | `/api/v1/login` | User authentication | ❌ |
| POST | `/api/v1/register` | User registration | ❌ |
| POST | `/api/v1/refresh` | Refresh JWT token | ❌ |
| POST | `/api/v1/logout` | User logout | ✅ |
| GET | `/api/v1/user` | Get authenticated user profile | ✅ |
| GET | `/api/v1/personas` | Get all personas (public) | ❌ |
| GET | `/api/v1/personas/{id}` | Get specific persona (public) | ❌ |
| POST | `/api/v1/personas` | Create new persona | ✅ |
| PUT | `/api/v1/personas/{id}` | Update persona | ✅ |
| DELETE | `/api/v1/personas/{id}` | Delete persona | ✅ |

### Example Request

```bash
# User Registration
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secure_password",
    "password_confirmation": "secure_password"
  }'
```


### Response Format

```json
{
  "success": true,
  "message": "Request processed successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "meta": {
    "timestamp": "2025-09-15T04:10:00Z",
    "version": "1.0.0"
  }
}
```


## 🧪 Testing

### Running Tests

```bash
# Run all tests
docker-compose exec app php artisan test

# Run tests with coverage (if configured)
docker-compose exec app php artisan test --coverage

# Run specific test suite
docker-compose exec app php artisan test --testsuite=Feature
docker-compose exec app php artisan test --testsuite=Unit

# Run specific test file
docker-compose exec app php artisan test tests/Feature/AuthTest.php

# Run tests with detailed output
docker-compose exec app php artisan test --verbose

# Create new test
docker-compose exec app php artisan make:test ExampleTest
docker-compose exec app php artisan make:test ExampleTest --unit
```

### Test Database

```bash
# Create test database (if needed)
docker-compose exec db createdb -U webai_user webai_test

# Run migrations for testing
docker-compose exec app php artisan migrate --env=testing
```

## 🚀 Production Deployment

### Environment Configuration

1. **Create Production Environment File**
```bash
cp .env.example .env.production
```

2. **Configure Production Values**
```bash
# Essential production settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (use your production database credentials)
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=your-production-db
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password

# JWT Secret (generate a secure key)
JWT_SECRET=your-jwt-secret-key

# Cache and Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=your-redis-host
```

### Optimization Commands

```bash
# Production optimization (run inside container)
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan optimize

# Build production assets
docker-compose exec app npm run build
```

### Database Setup for Production

```bash
# Run migrations (without --force prompts in production)
docker-compose exec app php artisan migrate --force

# Seed production data (if needed)
docker-compose exec app php artisan db:seed --class=ProductionSeeder
```

### Environment Variables

Never commit sensitive data. Key environment variables:

```env
APP_KEY=base64:generated_key_here
DB_PASSWORD=your_secure_password
JWT_SECRET=your_jwt_secret_key
```


### Security Features

- JWT token authentication with configurable expiration
- Rate limiting on API endpoints
- CORS protection with configurable origins
- Input validation and sanitization
- SQL injection prevention via Eloquent ORM
- XSS protection with Laravel's built-in features


## 📋 Quick Reference

### Essential Commands Cheat Sheet

```bash
# Project Setup
git clone https://github.com/ashanr-qualitapps/webai_backend_api.git
cd webai_backend_api
cp .env.docker .env
docker-compose up --build -d
docker-compose exec app composer install
docker-compose exec app php artisan migrate --seed

# Daily Development
docker-compose up -d                              # Start containers
docker-compose exec app php artisan migrate       # Run migrations
docker-compose exec app php artisan test          # Run tests
docker-compose exec app php artisan route:list    # View routes
docker-compose logs -f app                        # View logs
docker-compose down                               # Stop containers

# Database Operations
docker-compose exec app php artisan migrate:fresh --seed  # Reset DB
docker-compose exec db psql -U webai_user -d webai_db    # Access DB
docker-compose exec app php artisan make:migration name   # New migration

# Troubleshooting
docker-compose exec app php artisan optimize:clear       # Clear caches
docker-compose exec app chmod -R 775 storage            # Fix permissions
docker-compose restart app                              # Restart app
```

### Project URLs
- **Application:** http://localhost:8000
- **API Base:** http://localhost:8000/api/v1
- **Database:** localhost:5432


## � Security
