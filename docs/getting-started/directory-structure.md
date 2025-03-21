# Directory Structure

Titan's directory structure is designed to be intuitive and organized. Each directory has a specific purpose and contains related files. This page provides an overview of the default directory structure of a Titan application.

## The Root Directory

The root directory of a Titan application contains various folders and files:

```
app/                # Application code
bootstrap/          # Framework bootstrapping files
config/             # Configuration files
database/           # Database migrations, seeders, and factories
docs/               # Documentation
public/             # Publicly accessible files (entry point)
resources/          # Views, uncompiled assets, and language files
routes/             # Route definitions
storage/            # Application generated files
tests/              # Automated tests
vendor/             # Composer dependencies
.env                # Environment-specific configuration
.env.example        # Example environment configuration
composer.json       # Composer dependencies file
README.md           # Project documentation
```

Let's explore each of these directories and their contents in more detail.

## The App Directory

The `app` directory contains the core code of your application. This is where most of your application's logic resides:

```
app/
├── Controllers/    # HTTP controllers
├── Models/         # Data models
├── Middleware/     # HTTP middleware
├── Providers/      # Service providers
└── Services/       # Business logic services
```

### Controllers

The `Controllers` directory contains the application's controllers, which handle HTTP requests and return responses. Controllers in Titan are organized in a clean, simple way:

```php
namespace App\Controllers;

use Titan\Http\Request;
use Titan\Http\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('welcome');
    }
}
```

### Models

The `Models` directory contains data models, which represent and interact with your database tables:

```php
namespace App\Models;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    protected array $hidden = [
        'password',
        'remember_token',
    ];
}
```

### Middleware

The `Middleware` directory contains middleware classes, which filter HTTP requests entering your application:

```php
namespace App\Middleware;

use Titan\Http\Request;
use Titan\Http\Response;
use Titan\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): ?Response
    {
        if (!$request->session()->has('user_id')) {
            return redirect('/login');
        }

        return null;
    }
}
```

### Providers

The `Providers` directory contains service providers, which bootstrap your application by binding services in the service container, registering event listeners, etc.:

```php
namespace App\Providers;

use Titan\Core\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bindings in the service container
    }

    public function boot(): void
    {
        // Perform bootstrapping tasks
    }
}
```

### Services

The `Services` directory contains business logic services, which encapsulate complex application logic:

```php
namespace App\Services;

class PaymentService
{
    public function processPayment(array $paymentData): bool
    {
        // Process payment logic here
    }
}
```

## The Bootstrap Directory

The `bootstrap` directory contains files that bootstrap the framework:

```
bootstrap/
├── app.php        # Application bootstrapping
└── cache/         # Framework generated cache files
```

The `app.php` file is the central file used to bootstrap the Titan framework. It creates the application instance and configures the basic services.

## The Config Directory

The `config` directory contains all of your application's configuration files:

```
config/
├── app.php        # Application configuration
├── auth.php       # Authentication configuration
├── cache.php      # Cache configuration
├── database.php   # Database configuration
├── logging.php    # Logging configuration
├── mail.php       # Mail configuration
├── session.php    # Session configuration
└── view.php       # View configuration
```

Each configuration file returns an array of configurations. These files provide a clean way to organize your application's configuration values.

## The Database Directory

The `database` directory contains database migrations, seeders, and factories:

```
database/
├── migrations/     # Database migrations
├── seeds/          # Database seeders
└── factories/      # Model factories
```

### Migrations

Migrations are used to build and modify your database schema over time:

```php
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('users', function (Table $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('users');
    }
}
```

### Seeds

Seeds are used to populate your database with test data:

```php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
```

### Factories

Factories are used to generate model instances for testing and development:

```php
class UserFactory extends Factory
{
    protected string $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
        ];
    }
}
```

## The Public Directory

The `public` directory contains the entry point for all requests entering your application, as well as your assets such as images, JavaScript, and CSS:

```
public/
├── index.php       # Application entry point
├── .htaccess       # Apache URL rewriting rules
├── favicon.ico     # Favicon
├── css/            # CSS files
├── js/             # JavaScript files
└── img/            # Images
```

The `index.php` file is the entry point for all HTTP requests and initializes the Titan framework.

## The Resources Directory

The `resources` directory contains views, raw assets, and language files:

```
resources/
├── views/          # Templates
├── assets/         # Raw, uncompiled assets (SASS, JS, etc.)
└── lang/           # Language files
```

### Views

The `views` directory contains your application's templates:

```php
<!-- resources/views/welcome.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Titan</title>
</head>
<body>
    <h1>Welcome to Titan Framework</h1>
    <p>A powerful, secure, and developer-friendly PHP framework.</p>
</body>
</html>
```

## The Routes Directory

The `routes` directory contains all of the route definitions for your application:

```
routes/
├── web.php         # Web routes
├── api.php         # API routes
├── console.php     # Console routes
└── channels.php    # WebSocket channels
```

### Web Routes

The `web.php` file contains web routes that the RouteServiceProvider places in the web middleware group:

```php
use Titan\Core\Facades\Route;

Route::get('/', 'HomeController@index');
Route::get('/about', 'HomeController@about');
Route::get('/contact', 'HomeController@contact');
```

### API Routes

The `api.php` file contains API routes that the RouteServiceProvider places in the api middleware group:

```php
use Titan\Core\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::get('/users', 'Api\UserController@index');
    Route::get('/users/{id}', 'Api\UserController@show');
});
```

## The Storage Directory

The `storage` directory contains application generated files such as cache, logs, and file uploads:

```
storage/
├── app/            # Application generated files
│   └── public/     # User-generated files (publicly accessible)
├── logs/           # Log files
└── cache/          # Cache files
```

The `app/public` directory can contain user-generated files (like profile images) that should be publicly accessible. You should create a symbolic link from `public/storage` to `storage/app/public` to make these files accessible from the web.

## The Tests Directory

The `tests` directory contains your automated tests:

```
tests/
├── Feature/        # Feature tests
├── Unit/           # Unit tests
└── TestCase.php    # Base test case
```

## The Vendor Directory

The `vendor` directory contains your Composer dependencies. This directory is generated by Composer and should not be committed to version control.

## Customizing the Directory Structure

While Titan provides a default directory structure, you're free to organize your code however you want. The framework is designed to be flexible, and many of the directories can be moved or renamed to suit your needs.

However, there are a few directories that must remain in their default locations for the framework to function properly:

- `bootstrap/`
- `config/`
- `public/`
- `storage/`

The rest of the directory structure can be customized as needed.

## Next Steps

Now that you understand the directory structure of a Titan application, you can proceed to:

- [Configure your application](configuration.md)
- [Build your first project](first-project.md)
