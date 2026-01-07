# AGENTS.md - Symfony Application Development Guidelines

This file contains essential information for AI coding agents working on this Symfony 8.0 application.

## Project Overview
- **Framework**: Symfony 8.0 (MicroKernel architecture)
- **PHP Version**: 8.4+
- **Namespace**: App\
- **Autoloading**: PSR-4 (src/ → App\)

## Build Commands

### Installation
```bash
composer install
```

### Development Server
```bash
php bin/console cache:clear
php bin/console debug:router
php bin/console debug:container
```

### Cache Management
```bash
php bin/console cache:clear
php bin/console cache:warmup
```

## Testing Commands

### Recommended Test Setup
Install PHPUnit and testing dependencies:
```bash
composer require --dev phpunit/phpunit symfony/test-pack
```

### Test Commands (After Setup)
```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Controller/ExampleTest.php

# Run single test method
php bin/phpunit --filter testExampleMethod

# Run tests with coverage
php bin/phpunit --coverage-html var/coverage

# Run tests in verbose mode
php bin/phpunit -v
```

### Test File Structure
```
tests/
├── Controller/
│   └── ExampleControllerTest.php
├── Entity/
│   └── ExampleEntityTest.php
└── Functional/
    └── ExampleFunctionalTest.php
```

## Linting & Code Quality

### Recommended Linting Setup
```bash
composer require --dev phpstan/phpstan symfony/maker-bundle
composer require --dev friendsofphp/php-cs-fixer
```

### Linting Commands (After Setup)
```bash
# PHPStan static analysis
php vendor/bin/phpstan analyse src/

# PHP CS Fixer (check only)
php vendor/bin/php-cs-fixer fix --dry-run --diff

# PHP CS Fixer (auto-fix)
php vendor/bin/php-cs-fixer fix
```

## Code Style Guidelines

### PHP Standards
- Follow PSR-12 coding standards
- Use PHP 8.4+ features (typed properties, union types, etc.)
- Declare strict types: `declare(strict_types=1);`
- Use namespaces and imports properly

### File Structure
```
src/
├── Controller/     # HTTP controllers
├── Entity/         # Doctrine entities
├── Repository/     # Doctrine repositories
├── Service/        # Business logic services
├── Form/           # Symfony forms
├── Event/          # Event classes
└── EventSubscriber/ # Event subscribers
```

### Naming Conventions

#### Classes
- Use PascalCase: `UserController`, `ProductService`
- Interfaces: `UserRepositoryInterface`
- Traits: `TimestampableTrait`

#### Methods
- camelCase: `getUserById()`, `createNewProduct()`
- Boolean methods: `isActive()`, `hasPermission()`
- Action methods in controllers: `index()`, `show()`, `create()`

#### Properties & Variables
- camelCase: `$userName`, `$productList`
- Private properties: `$_userName` (with underscore prefix)
- Constants: `UPPER_SNAKE_CASE`

#### Files
- Class files: `UserController.php`
- Match namespace structure in directory hierarchy

### Import Organization
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\UserService;
use App\Entity\User;

// Group imports by:
// 1. Symfony core classes
// 2. Third-party libraries
// 3. Application classes
```

### Controller Guidelines
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    #[Route('/users', name: 'user_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();

        return $this->json($users);
    }
}
```

### Service Guidelines
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }
}
```

### Entity Guidelines
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    // Getters and setters...
}
```

### Error Handling
```php
<?php

// Use Symfony exceptions
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

// In controllers
throw new NotFoundHttpException('User not found');

// Custom exceptions
class UserNotFoundException extends \Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("User with ID {$userId} not found");
    }
}
```

### Security Best Practices
- Never log sensitive data (passwords, tokens, secrets)
- Use environment variables for secrets
- Validate and sanitize all inputs
- Use Symfony's security components
- Implement proper authentication/authorization

### Testing Guidelines
```php
<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $client->request('GET', '/users');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }
}
```

## Formatting & EditorConfig

Based on `.editorconfig`:
- **Indentation**: 4 spaces
- **Charset**: UTF-8
- **Line endings**: LF (Unix)
- **Final newline**: Required
- **Trim trailing whitespace**: Yes

## Git Workflow

### Commit Messages
```
feat: add user authentication
fix: resolve database connection issue
docs: update API documentation
refactor: simplify user service logic
test: add unit tests for user service
```

### Branch Naming
- Feature branches: `feature/user-authentication`
- Bug fixes: `fix/database-connection-issue`
- Hotfixes: `hotfix/security-patch`

## Performance Considerations
- Use dependency injection properly
- Cache expensive operations
- Optimize database queries
- Use Symfony's profiler for debugging
- Implement proper indexing on entities

## Documentation
- Use PHPDoc comments for classes and methods
- Document complex business logic
- Keep README.md updated
- Use Symfony's routing annotations

## Development Environment
- Use PHP 8.4+
- Enable all error reporting in development
- Use Symfony's debug toolbar
- Configure IDE with proper PHPStan/PHP CS Fixer integration