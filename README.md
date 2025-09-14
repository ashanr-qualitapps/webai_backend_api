# WebAI Backend API

A powerful Laravel-based REST API backend for AI-powered web applications, featuring PostgreSQL database integration and containerized development environment.

[
[
[
[

## 🚀 About This Project

WebAI Backend API is a robust, scalable backend service designed to power AI-integrated web applications. Built with Laravel's elegant architecture, it provides RESTful endpoints for AI processing, user management, and data analytics with enterprise-grade security and performance optimization.

### Key Features

- 🤖 **AI Integration Ready** - Structured service layer for multiple AI providers
- 🔐 **Secure Authentication** - JWT-based API authentication with role-based permissions
- 🐘 **PostgreSQL Database** - Advanced relational database with AI-optimized schema
- 🐳 **Docker Support** - Complete containerized development and deployment
- 📊 **Real-time Processing** - Queue-based background job processing
- 🔍 **API Documentation** - Interactive Swagger/OpenAPI documentation
- ⚡ **Performance Optimized** - Redis caching and database query optimization
- 🧪 **Test Coverage** - Comprehensive PHPUnit testing suite


## 🏗️ Architecture



## 🛠️ Technology Stack

- **Backend Framework:** Laravel 10.x
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

```bash
# Copy the Docker environment configuration
cp .env.docker .env

# Optional: Customize your environment variables
nano .env
```


### 3. Build and Start Containers

```bash
# Build and start all services
docker-compose up --build -d

# Check if containers are running
docker-compose ps
```


### 4. Initialize the Application

```bash
# Install dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run database migrations
docker-compose exec app php artisan migrate

# Seed the database (optional)
docker-compose exec app php artisan db:seed
```


### 5. Verify Installation

- **API Base URL:** http://localhost:9000
- **Health Check:** http://localhost:9000/api/health
- **API Documentation:** http://localhost:9000/api/documentation


## 🔧 Development

### Container Services

| Service | URL | Credentials |
| :-- | :-- | :-- |
| Laravel App | http://localhost:9000 | - |
| PostgreSQL | localhost:5432 | User: `laravel`, Password: `secret` |
| Redis | localhost:6379 | No auth (development) |

### Common Commands

```bash
# View application logs
docker-compose logs -f app

# Access application container
docker-compose exec app bash

# Run Artisan commands
docker-compose exec app php artisan [command]

# Run tests
docker-compose exec app php artisan test

# Stop all containers
docker-compose down

# Rebuild containers
docker-compose up --build
```


### Database Management

```bash
# Run fresh migrations with seeding
docker-compose exec app php artisan migrate:fresh --seed

# Create new migration
docker-compose exec app php artisan make:migration create_example_table

# Access PostgreSQL directly
docker-compose exec postgres psql -U laravel -d laravel
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
| POST | `/api/auth/login` | User authentication | ❌ |
| POST | `/api/auth/register` | User registration | ❌ |
| GET | `/api/user/profile` | Get user profile | ✅ |
| POST | `/api/ai/process` | Process AI request | ✅ |
| GET | `/api/ai/history` | Get AI request history | ✅ |

### Example Request

```bash
# User Registration
curl -X POST http://localhost:9000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secure_password"
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

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test suite
docker-compose exec app php artisan test --testsuite=Feature

# Run tests with coverage
docker-compose exec app php artisan test --coverage

# Run specific test file
docker-compose exec app php artisan test tests/Feature/AuthTest.php
```


## 🔐 Security

### Environment Variables

Never commit sensitive data. Key environment variables:

```env
APP_KEY=base64:generated_key_here
DB_PASSWORD=your_secure_password
JWT_SECRET=your_jwt_secret_key
OPENAI_API_KEY=your_openai_key
```


### Security Features

- JWT token authentication with configurable expiration
- Rate limiting on API endpoints
- CORS protection with configurable origins
- Input validation and sanitization
- SQL injection prevention via Eloquent ORM
- XSS protection with Laravel's built-in features


## 📦 Deployment

### Production Environment

1. **Environment Configuration**
```bash
cp .env.production .env
# Configure production values
```

2. **Optimize for Production**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

3. **Database Setup**
```bash
php artisan migrate --force
```


### Docker Production Build

```dockerfile
# Use production Dockerfile
docker build -f Dockerfile.prod -t webai-backend:latest .
```
