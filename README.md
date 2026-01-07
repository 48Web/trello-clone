# Trello Clone - Symfony 8

A fully functional Trello clone built with Symfony 8, featuring MySQL, Redis, and Cloudflare R2 integration for Laravel Cloud deployment testing.

## 🚀 Features

- ✅ **Boards**: Create and manage work boards with automatic default lists
- ✅ **Lists**: Organize cards into columns/lists with drag & drop reordering
- ✅ **Cards**: Add tasks with titles, descriptions, and image attachments
- ✅ **Attachments**: Upload images to cards (Cloudflare R2 storage)
- ✅ **Drag & Drop**: Full kanban functionality with SortableJS
- ✅ **Real-time Updates**: Polling-based updates every 10 seconds
- ✅ **REST API**: Complete JSON API for all operations
- ✅ **Default Lists**: New boards automatically get "My First List"

## 🛠️ Tech Stack

- **Framework**: Symfony 8.0 (MicroKernel)
- **Database**: SQLite (dev) / MySQL 8.0 (production) with Doctrine ORM
- **Cache/Storage**: Redis (phpredis) + Cloudflare R2
- **Frontend**: Twig templates + Bootstrap + Vanilla JavaScript
- **File Storage**: AWS S3-compatible (Cloudflare R2)
- **Development**: Symfony CLI, Composer, Doctrine Migrations

## 🚀 Quick Start

### Prerequisites
- PHP 8.4+
- Composer
- Symfony CLI (optional)

### 1. Install Dependencies
```bash
composer install
```

### 2. Environment Setup
The application is pre-configured with SQLite for development. For production, update `.env` with:

```bash
# Production Database (MySQL)
DATABASE_URL="mysql://user:password@host:port/database?serverVersion=8.0"

# Redis (for caching/sessions)
REDIS_URL="redis://host:port"

# Cloudflare R2 (for file storage)
CLOUDFLARE_R2_ENDPOINT="https://your-account-id.r2.cloudflarestorage.com"
CLOUDFLARE_R2_REGION="auto"
CLOUDFLARE_R2_ACCESS_KEY="your-access-key"
CLOUDFLARE_R2_SECRET_KEY="your-secret-key"
CLOUDFLARE_R2_BUCKET="trello-attachments"
```

### 3. Database Setup
```bash
# Run existing migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Load sample data
php bin/console doctrine:fixtures:load --no-interaction
```

### 4. Start Development Server
```bash
# Clear cache
php bin/console cache:clear

# Start server
symfony serve
# or
php bin/console cache:clear && symfony serve --daemon
```

**Visit `https://symfonytest.test` to access the application!** 🎉

## 🎮 Usage

### Dashboard (`/`)
- View all your boards
- Create new boards (automatically get "My First List")
- Click on any board to enter kanban view

### Board View (`/boards/{id}`)
- **Lists**: Add new lists, drag to reorder
- **Cards**: Create, edit, move between lists with drag & drop
- **Real-time**: Updates every 10 seconds
- **Attachments**: Upload images to cards (R2 ready)

### Sample Data
The application includes sample data:
- **Welcome Board**: Complete with To Do, In Progress, Done lists
- **Demo Cards**: Sample tasks to demonstrate functionality
- **Default Lists**: New boards automatically get "My First List"

## 📡 API Endpoints

### Boards
- `GET /api/boards` - List all boards
- `POST /api/boards` - Create board (includes automatic "My First List")
- `GET /api/boards/{id}` - Get board details with lists and cards
- `PUT /api/boards/{id}` - Update board
- `DELETE /api/boards/{id}` - Delete board

### Lists
- `POST /api/boards/{boardId}/lists` - Create list in board
- `PUT /api/lists/{id}` - Update list
- `PUT /api/lists/{id}/position` - Reorder list (drag & drop)
- `DELETE /api/lists/{id}` - Delete list

### Cards
- `GET /api/lists/{listId}/cards` - Get cards in list
- `POST /api/lists/{listId}/cards` - Create card
- `PUT /api/cards/{id}` - Update card (title/description)
- `PUT /api/cards/{id}/position` - Move card between lists (drag & drop)
- `DELETE /api/cards/{id}` - Delete card

### Attachments
- `POST /api/cards/{cardId}/attachments` - Upload image to card
- `GET /api/attachments/{id}/download` - Download attachment
- `DELETE /api/attachments/{id}` - Delete attachment

### Example API Usage
```bash
# Create a new board (automatically gets "My First List")
curl -X POST -H "Content-Type: application/json" \
  -d '{"title":"My Project","description":"Project management board"}' \
  http://localhost:8000/api/boards

# Add a card to a list
curl -X POST -H "Content-Type: application/json" \
  -d '{"title":"Implement feature","description":"Add new functionality"}' \
  http://localhost:8000/api/lists/1/cards
```

## ☁️ Laravel Cloud Deployment

This application is **production-ready** and designed to thoroughly test Laravel Cloud deployments with all three services:

### 🗄️ Database Testing (MySQL)
- ✅ Full CRUD operations across all entities
- ✅ Complex relationships (Board → Lists → Cards → Attachments)
- ✅ Doctrine migrations for schema management
- ✅ Fixtures for sample data

### ⚡ Redis Testing (phpredis)
- ✅ Cache configuration ready
- ✅ Session storage configured
- ✅ Doctrine result/query caching enabled
- ✅ Rate limiting capability prepared

### 📁 File Storage Testing (Cloudflare R2)
- ✅ AWS S3-compatible adapter configured
- ✅ File upload infrastructure ready
- ✅ Image attachment support
- ✅ CDN-ready for global delivery

### 🚀 Deployment Checklist
- [x] **Environment Variables**: Configure DATABASE_URL, REDIS_URL, R2 credentials
- [x] **Database**: MySQL instance with migration support
- [x] **Redis**: Instance for caching and sessions
- [x] **Cloudflare R2**: Bucket and API credentials
- [x] **Migrations**: Run `doctrine:migrations:migrate`
- [x] **Fixtures**: Load sample data with `doctrine:fixtures:load`
- [x] **API Testing**: All endpoints functional
- [x] **Frontend**: Dashboard and kanban board working
- [x] **File Uploads**: Attachment system operational

### 📊 Current Status: **READY FOR DEPLOYMENT** ✅

The application successfully tests the complete Laravel Cloud stack and is ready for production deployment!

## Development

### Code Quality
```bash
# PHPStan (install first)
composer require --dev phpstan/phpstan
php vendor/bin/phpstan analyse src/

# PHP CS Fixer (install first)
composer require --dev friendsofphp/php-cs-fixer
php vendor/bin/php-cs-fixer fix
```

### Testing
```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Run tests
php bin/phpunit
```

## 📁 Project Structure

```
├── .product/plans/          # Project planning and changelog
├── config/                  # Symfony configuration files
│   ├── packages/           # Bundle configurations
│   └── routes.yaml         # Route definitions
├── migrations/             # Doctrine database migrations
├── public/                 # Web assets and entry point
├── src/                    # Application source code
│   ├── Controller/         # API and frontend controllers
│   ├── DataFixtures/       # Sample data fixtures
│   ├── Entity/            # Doctrine entities (User, Board, List, Card, Attachment)
│   ├── Repository/        # Doctrine repositories with custom methods
│   └── Service/           # Business logic services (R2 client)
├── templates/             # Twig templates (dashboard, board view)
├── tests/                 # Test directory (ready for expansion)
├── var/                   # Cache, logs, database files
├── vendor/                # Composer dependencies
├── .env                   # Environment configuration
├── AGENTS.md             # AI coding agent guidelines
├── composer.json         # PHP dependencies
├── README.md             # This file
└── symfony.lock          # Symfony version lock
```

## 🔄 Development Roadmap

### ✅ Completed
- Full Trello clone with boards, lists, cards
- MySQL/SQLite database integration
- Redis caching configuration
- Cloudflare R2 file storage setup
- Drag & drop kanban interface
- REST API with JSON responses
- Automatic default lists for new boards
- Laravel Cloud deployment ready

### 🚀 Future Enhancements
- User authentication and collaboration
- WebSocket real-time updates (Laravel Reverb)
- Advanced file management (thumbnails, multiple formats)
- Team management and permissions
- Advanced search and filtering
- Mobile-responsive design improvements
- Comprehensive test suite

## 🤝 Contributing

1. Follow Symfony 8 coding standards
2. Use PHP 8.4+ features and attributes
3. Add comprehensive tests for new features
4. Update documentation and changelog
5. Ensure Laravel Cloud compatibility

## 📄 License

This project is for educational and Laravel Cloud deployment testing purposes.

---

**🎉 Built with Symfony 8 - Ready for Laravel Cloud!**