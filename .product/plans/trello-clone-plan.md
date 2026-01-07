# Trello Clone Implementation Plan

## Overview
Build a simplified Trello clone using Symfony 8 with MySQL, Redis, and Cloudflare R2 storage for Laravel Cloud deployment testing.

## Core Features
- Single user system (no authentication/collaboration)
- Boards, Lists (columns), Cards with drag & drop
- Card features: title, description, single image attachment
- Polling-based real-time updates
- Basic Twig frontend with drag & drop functionality

## Technology Stack
- **Framework**: Symfony 8.0 (MicroKernel)
- **Database**: MySQL 8.0+ with Doctrine ORM
- **Cache/Storage**: Redis (phpredis) + Cloudflare R2
- **Frontend**: Twig templates + Vanilla JavaScript
- **Testing**: PHPUnit with comprehensive coverage

## Database Schema

### Entities
1. **User** - Single user for ownership
2. **Board** - User's boards with title, description, position
3. **BoardList** - Columns within boards (Lists)
4. **Card** - Tasks with title, description, position, single attachment
5. **Attachment** - Image files stored in Cloudflare R2

### Relationships
- User → Boards (1:N)
- Board → BoardLists (1:N)
- BoardList → Cards (1:N)
- Card → Attachment (1:1)

## API Endpoints

### Boards
```
GET    /api/boards          # List user's boards
POST   /api/boards          # Create board
GET    /api/boards/{id}     # Get board with lists/cards
PUT    /api/boards/{id}     # Update board
DELETE /api/boards/{id}     # Delete board
```

### Lists (Columns)
```
POST   /api/boards/{id}/lists    # Create list in board
PUT    /api/lists/{id}           # Update list
PUT    /api/lists/{id}/position  # Reorder list
DELETE /api/lists/{id}           # Delete list
```

### Cards
```
GET    /api/lists/{id}/cards     # Get cards in list
POST   /api/lists/{id}/cards     # Create card
PUT    /api/cards/{id}           # Update card
PUT    /api/cards/{id}/position  # Move card (drag & drop)
DELETE /api/cards/{id}           # Delete card
```

### Attachments
```
POST   /api/cards/{id}/attachments  # Upload image
GET    /api/attachments/{id}        # Get attachment URL
DELETE /api/attachments/{id}        # Delete attachment
```

## Frontend Pages
1. **Dashboard** (`/`) - List all boards
2. **Board View** (`/boards/{id}`) - Kanban board with drag & drop
3. **Card Modal** - Edit card details and upload images

## Implementation Phases

### Phase 1: Project Setup
- Install Doctrine, phpredis, Flysystem R2 adapter
- Configure database, Redis, and R2 connections
- Set up basic project structure

### Phase 2: Database Layer
- Create Doctrine entities
- Set up database migrations
- Configure repositories

### Phase 3: API Layer
- Create controllers for all endpoints
- Implement CRUD operations
- Add validation and error handling

### Phase 4: Storage Integration
- Configure Cloudflare R2 adapter
- Implement file upload/download
- Add image processing (resizing)

### Phase 5: Frontend Development
- Create Twig templates
- Implement drag & drop with SortableJS
- Add polling for real-time updates

### Phase 6: Testing & Deployment
- Write comprehensive tests
- Configure deployment manifests
- Add health checks for all services

## Testing Strategy
- **Unit Tests**: Service classes and utilities
- **Integration Tests**: Database, Redis, and R2 operations
- **API Tests**: Full endpoint testing
- **Frontend Tests**: Basic integration tests

## Deployment Considerations
- Environment-specific configurations
- Database migration strategy
- Redis connection pooling
- R2 bucket and CDN setup
- Health check endpoints

## Success Criteria
- All CRUD operations work with MySQL
- Redis caching and sessions function properly
- File uploads work with Cloudflare R2
- Drag & drop functionality is smooth
- Polling updates work reliably
- Application deploys successfully to Laravel Cloud