-- Initialize the database with pgvector extension
-- This script is executed when the PostgreSQL container starts for the first time

-- Create the pgvector extension if it doesn't exist
CREATE EXTENSION IF NOT EXISTS vector;

-- Optional: Create any additional extensions or configurations here
-- CREATE EXTENSION IF NOT EXISTS "uuid-ossp";