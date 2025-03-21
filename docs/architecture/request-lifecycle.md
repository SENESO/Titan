# Request Lifecycle

Understanding the request lifecycle in Titan is essential for developing applications effectively. This document explains how a request flows through the Titan framework, from the initial HTTP request to the final response.

## Overview

The request lifecycle follows these main steps:

1. **Entry Point**: The request enters through `public/index.php`
2. **Bootstrapping**: The application is bootstrapped
3. **Middleware**: The request passes through global middleware
4. **Routing**: The router matches the request to a route
5. **Controller/Action**: The controller action is executed
6. **Response**: A response is generated and returned
7. **Termination**: The application terminates and the response is sent

Let's explore each step in detail.

## Entry Point

All requests to a Titan application enter through the `public/index.php` file. This file serves as the entry point for your application and is responsible for loading the necessary components to handle the request.

```php
<?php

// Define the application start time
define('TITAN_START', microtime(true));

// Load the composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Get the application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Run the application
$response = $app->run();

// Send the response
$response->send();
```

The entry point file does the following:

1. Records the start time of the application
2. Loads the Composer autoloader to enable class autoloading
3. Loads the application instance from `bootstrap/app.php`
4. Runs the application, which returns a response
5. Sends the response back to the client

## Bootstrapping

The bootstrapping phase is where the application initializes all the components it needs to handle the request. This happens in the `bootstrap/app.php` file and the `Application` class.

### Bootstrap File

The `bootstrap/app.php` file creates a new instance of the `Application` class and configures it:

```php
<?php

use Titan\Core\Application;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Titan application instance
| which serves as the "glue" for the components of the framework.
|
*/

$app = new Application(
    realpath(__DIR__ . '/..')
);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| the central way of configuring and bootstrapping the framework.
|
*/

// Register core service providers
// $app->register(new \App\Providers\AppServiceProvider($app));

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application.
|
*/

return $app;
```

### Application Bootstrapping

When the `Application::run()` method is called, the application goes through a series of bootstrapping steps:

1. **Load Environment Variables**: Loads environment-specific configuration from the `.env` file
2. **Load Configuration**: Loads configuration files from the `config` directory
3. **Register Error Handlers**: Sets up error and exception handlers
4. **Register Facades**: Registers facade aliases for easy access to services
5. **Register Core Service Providers**: Registers and boots core service providers
6. **Register Application Service Providers**: Registers and boots application service providers

This bootstrapping process prepares all the components needed to handle the request.

## Middleware

After the application is bootstrapped, the request is passed through a series of middleware. Middleware provides a convenient mechanism for filtering and modifying HTTP requests entering your application.

### Global Middleware

Global middleware runs on every request and is registered in the `app/Providers/MiddlewareServiceProvider.php` file.

Typical global middleware might include:

- **TrustProxies**: Trust proxies to correctly set client IP addresses and protocol
- **CheckForMaintenanceMode**: Check if the application is in maintenance mode
- **ValidatePostSize**: Validate the size of POST data
- **TrimStrings**: Trim whitespace from request input
- **ConvertEmptyStringsToNull**: Convert empty strings to null values
- **AddQueuedCookiesToResponse**: Add queued cookies to the response

### Route Middleware

Route middleware runs only on specific routes or groups of routes. These are defined when registering routes.

```php
Route::get('/profile', 'ProfileController@show')->middleware('auth');

Route::group(['middleware' => ['auth', 'admin']], function () {
    Route::get('/admin', 'AdminController@index');
});
```

## Routing

After passing through middleware, the request is processed by the router. The router matches the HTTP request to the appropriate route defined in your application.

The routing process follows these steps:

1. **Get the HTTP Method and URI**: Determine the HTTP method (GET, POST, etc.) and URI path from the request
2. **Find Matching Routes**: Search for routes that match the HTTP method and URI pattern
3. **Route Parameters**: Extract any route parameters from the URI
4. **Route Resolution**: Determine the action to execute (closure or controller method)

If no matching route is found, a `RouteNotFoundException` is thrown, which typically results in a 404 response.

## Controller/Action

Once the router has determined which action to execute, it's time to run that action. This could be a closure defined directly in the route, or more commonly, a controller method.

### Controller Resolution

If the route's action is a controller, the following steps occur:

1. **Resolve the Controller**: Create an instance of the controller class
2. **Inject Dependencies**: Use the service container to inject any dependencies into the controller's constructor
3. **Prepare Method Parameters**: Resolve any method parameters using type hinting and route parameters
4. **Execute the Method**: Call the controller method with the resolved parameters

Here's an example of a controller action being executed:

```php
namespace App\Controllers;

use Titan\Http\Request;
use Titan\Http\Response;
use App\Models\User;

class UserController extends Controller
{
    public function show(Request $request, User $user): Response
    {
        return $this->view('users.show', [
            'user' => $user
        ]);
    }
}
```

In this example, the `Request` object is automatically injected, and the `User` model is automatically resolved from the route parameter using Route Model Binding.

## Response

After the controller action is executed, a response is generated. This response could be a view, JSON data, a redirect, or any other type of HTTP response.

Titan's response system is flexible and provides various ways to return different types of responses:

```php
// Return a view
return $this->view('users.index', ['users' => $users]);

// Return JSON
return $this->json(['name' => 'John', 'email' => 'john@example.com']);

// Return a redirect
return $this->redirect('/dashboard');

// Return a specific status code
return $this->response('Not Found', 404);
```

Each of these methods returns a `Response` object, which encapsulates the HTTP response data, including status code, headers, and content.

## Termination

After the response is generated, the application terminates and the response is sent back to the client.

### Response Sending

The `Response::send()` method does the following:

1. **Send HTTP Status Code**: Sets the HTTP status code of the response
2. **Send Headers**: Sends all HTTP headers, including cookies
3. **Send Content**: Outputs the response content
4. **Terminate Application**: Performs any final cleanup tasks

### Application Cleanup

During termination, the application performs cleanup tasks such as:

- **Closing Database Connections**: Close any open database connections
- **Persisting Cache**: Write any cached data to storage
- **Running Termination Callbacks**: Execute any registered termination callbacks

## Middleware In-Depth

Middleware plays a crucial role in the request lifecycle, running both before and after your application handles the request.

### Before & After Middleware

Middleware can perform actions before or after the request is handled by your application:

```php
namespace App\Middleware;

use Titan\Http\Request;
use Titan\Http\Response;
use Titan\Middleware\MiddlewareInterface;

class LogRequestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): ?Response
    {
        // Before the request is handled
        $this->logRequest($request);

        // Allow the request to continue
        return null;
    }
}
```

If a middleware needs to short-circuit the request handling process, it can return a `Response` object:

```php
namespace App\Middleware;

use Titan\Http\Request;
use Titan\Http\Response;
use Titan\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): ?Response
    {
        if (!$this->isAuthenticated($request)) {
            // Short-circuit the request handling process
            return redirect('/login');
        }

        // Allow the request to continue
        return null;
    }
}
```

### Middleware Order

The order in which middleware is executed is important. Global middleware runs first, followed by route middleware.

1. **Global Middleware**: Run on all requests, in the order defined in `MiddlewareServiceProvider`
2. **Route Middleware**: Run only on matching routes, in the order defined when registering the route

## Service Providers In-Depth

Service providers are a core part of the bootstrapping process and are responsible for binding services into the service container.

### Register & Boot Methods

Service providers have two main methods:

- **register()**: Bind services into the container
- **boot()**: Perform setup that requires services registered by other providers

```php
namespace App\Providers;

use Titan\Core\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind services into the container
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });
    }

    public function boot(): void
    {
        // Perform setup that requires resolved services
        $cache = $this->app->make('cache');
        $cache->setDefaultDriver('file');
    }
}
```

### Deferred Providers

Some service providers may be marked as "deferred", meaning they won't be loaded until one of their services is needed:

```php
namespace App\Providers;

use Titan\Core\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    protected bool $defer = true;

    public function register(): void
    {
        // Bind services into the container
    }

    public function provides(): array
    {
        return ['cache', 'cache.store'];
    }
}
```

## Request & Response Objects

The `Request` and `Response` objects are central to the request lifecycle.

### Request Object

The `Request` object encapsulates all the data from the HTTP request:

```php
// Get a query parameter
$page = $request->query('page', 1);

// Get a post parameter
$name = $request->post('name');

// Get all input
$input = $request->all();

// Get JSON data
$data = $request->json();

// Get a header
$contentType = $request->header('Content-Type');

// Get a cookie
$remember = $request->cookie('remember');

// Get the request method
$method = $request->method();

// Get the request path
$path = $request->path();
```

### Response Object

The `Response` object encapsulates all the data for the HTTP response:

```php
// Create a basic response
$response = new Response('Hello, World!', 200, ['Content-Type' => 'text/plain']);

// Create a JSON response
$response = Response::json(['name' => 'John', 'email' => 'john@example.com']);

// Create a view response
$response = Response::make(view('welcome', ['name' => 'John']), 200);

// Create a redirect response
$response = Response::redirect('/dashboard', 302);

// Add a header
$response->header('X-Custom-Header', 'value');

// Add a cookie
$response->cookie('remember', 'value', 60 * 24 * 30);
```

## Conclusion

Understanding the request lifecycle is crucial for building effective Titan applications. By knowing how a request flows through the framework, you can better utilize Titan's features and diagnose issues when they arise.

To summarize the request lifecycle:

1. The request enters through `public/index.php`
2. The application is bootstrapped through `bootstrap/app.php`
3. The request passes through global middleware
4. The router matches the request to a route
5. The controller action is executed
6. A response is generated and returned
7. The application terminates and the response is sent

With this knowledge, you're better equipped to develop robust applications with the Titan framework.

## Next Steps

Now that you understand the request lifecycle, you might want to learn more about:

- [Routing](../basics/routing.md): How to define routes in your application
- [Middleware](../basics/middleware.md): How to create and use middleware
- [Controllers](../basics/controllers.md): How to create and use controllers
- [Service Container](service-container.md): How dependency injection works in Titan
- [Service Providers](service-providers.md): How to configure your application
