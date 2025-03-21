# Titan PHP Framework

A powerful, secure, and developer-friendly PHP framework that goes beyond Laravel.

## Overview

Titan is a modern PHP framework designed to be more powerful, more secure, and easier to use than Laravel. It follows modern PHP practices, is fully PSR compliant, and provides a clean, elegant syntax for building web applications.

## Features

- **Ultra-fast Performance**: Optimized core with minimal overhead
- **Advanced Dependency Injection**: Powerful container with automatic resolution
- **Enhanced Security**: Built with security-first principles
- **Elegant Routing**: Intuitive and flexible router
- **Modern Architecture**: Follows PHP 8.2+ features and best practices
- **Comprehensive ORM**: Simple yet powerful database abstraction
- **PSR Compliance**: Follows PHP Standards Recommendations
- **Clean Code Structure**: Well-organized, maintainable code
- **Robust Request/Response**: Advanced HTTP handling
- **Powerful CLI Tools**: Feature-rich console commands
- **Comprehensive Documentation**: Easy to learn and use
- **Highly Extensible**: Easy to add or customize functionality

## Requirements

- PHP 8.2 or higher
- PHP extensions:
  - PDO
  - JSON
  - mbstring
  - Fileinfo

## Installation

### Via Composer Create-Project

```bash
composer create-project titan/titan your-project-name
```

### Manually

1. Clone the repository:

```bash
git clone https://github.com/titan/framework.git your-project-name
```

2. Install dependencies:

```bash
cd your-project-name
composer install
```

3. Copy `.env.example` to `.env` and configure your environment:

```bash
cp .env.example .env
```

4. Generate an application key:

```bash
php titan key:generate
```

## Quick Start

### Create a Route

In `routes/web.php`:

```php
use Titan\Core\Facades\Route;

Route::get('/hello/{name}', function ($name) {
    return "Hello, $name!";
});
```

### Create a Controller

```php
php titan make:controller HelloController
```

Edit `app/Controllers/HelloController.php`:

```php
namespace App\Controllers;

use Titan\Http\Request;
use Titan\Http\Response;

class HelloController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('hello', [
            'name' => $request->route('name', 'World')
        ]);
    }
}
```

Then update your route:

```php
Route::get('/hello/{name}', 'HelloController@index');
```

### Create a Model

```php
php titan make:model Post
```

Edit `app/Models/Post.php`:

```php
namespace App\Models;

class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = [
        'title',
        'content',
        'published'
    ];
}
```

## Directory Structure

```
app/
├── Controllers/    # Application controllers
├── Models/         # Data models
├── Middleware/     # HTTP middleware
├── Providers/      # Service providers
├── Services/       # Business logic services
bootstrap/          # Application bootstrapping files
config/             # Configuration files
database/
├── migrations/     # Database migrations
├── seeds/          # Database seeders
├── factories/      # Model factories
public/             # Publicly accessible files
resources/
├── views/          # View templates
├── assets/         # Uncompiled assets
routes/             # Route definitions
storage/            # Application generated files
tests/              # Automated tests
```

## Configuration

Titan uses environment-based configuration. The main configuration files are stored in the `config/` directory and use environment variables defined in the `.env` file.

### Key Configuration Files

- `app.php`: Application configuration
- `database.php`: Database configuration
- `cache.php`: Cache configuration
- `logging.php`: Logging configuration

## Security

Titan is built with a security-first mindset. It includes:

- CSRF protection
- XSS prevention
- SQL injection protection
- Authentication system with password hashing
- Authorization system with roles and permissions
- Input validation
- Rate limiting
- Secure headers

## Documentation

For complete documentation, visit [https://titan-framework.com/docs](https://titan-framework.com/docs).

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for more information.

## License

The Titan framework is open-source software licensed under the [MIT license](LICENSE).
