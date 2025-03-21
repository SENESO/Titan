# Controllers

Controllers are a key component in the Titan framework, providing a way to organize your application logic. They help you separate your business logic from your route definitions, resulting in cleaner and more maintainable code.

## Basic Controllers

Instead of defining all of your request handling logic as closures in route files, you might wish to organize this behavior using Controller classes. Controllers can group related request handling logic into a single class, allowing for better organization of your code.

Controllers are stored in the `app/Controllers` directory.

### A Basic Controller Example

Here's a basic example of a controller class:

```php
<?php

namespace App\Controllers;

use Titan\Http\Request;
use Titan\Http\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('users.index', [
            'users' => User::all()
        ]);
    }

    public function show(Request $request, $id): Response
    {
        return $this->view('users.show', [
            'user' => User::find($id)
        ]);
    }
}
```

### Registering Routes to the Controller

Once you have created a controller, you need to register routes to its methods:

```php
use Titan\Core\Facades\Route;

Route::get('/users', 'UserController@index');
Route::get('/users/{id}', 'UserController@show');
```

Now, when a request matches these routes, the corresponding controller method will be invoked, and the return value of the method will be sent back to the user as the response.

## The Base Controller Class

All Titan controllers should extend the base `Controller` class, which provides helpful methods like `view()`, `redirect()`, and `json()` for generating various types of responses.

The base controller is located in `app/Controllers/Controller.php`. Here's an example of the methods it provides:

```php
<?php

namespace App\Controllers;

use Titan\Http\Request;
use Titan\Http\Response;

abstract class Controller
{
    protected function view(string $view, array $data = [], int $status = 200, array $headers = []): Response
    {
        // Render a view template with data
    }

    protected function json($data, int $status = 200, array $headers = [], $options = 0): Response
    {
        // Return a JSON response
    }

    protected function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        // Redirect to another URL
    }

    protected function back(int $status = 302, array $headers = []): Response
    {
        // Redirect back to the previous URL
    }

    protected function notFound(string $message = 'Not Found'): Response
    {
        // Return a 404 Not Found response
    }

    protected function validationError(array $errors, int $status = 422): Response
    {
        // Return a validation error response
    }
}
```

## Single Action Controllers

If a controller only needs to handle a single action, you can use the `__invoke` method to define that action:

```php
<?php

namespace App\Controllers;

use Titan\Http\Request;
use Titan\Http\Response;

class ShowDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return $this->view('dashboard');
    }
}
```

You can register a route to a single action controller like this:

```php
Route::get('/dashboard', 'ShowDashboardController');
```

## Controller Middleware

Middleware can be assigned to the controller's routes in your route files:

```php
Route::get('/dashboard', 'DashboardController@index')->middleware('auth');
```

Or, you can specify middleware in the controller's constructor:

```php
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('log')->only('index');
        $this->middleware('subscribed')->except('store');
    }
}
```

The `middleware` method accepts an array of middleware names:

```php
$this->middleware(['auth', 'log']);
```

## Resource Controllers

Titan resource routing assigns the typical create, read, update, and delete ("CRUD") routes to a controller with a single line of code. For example, you might want to create a controller that handles all HTTP requests for "photos" stored by your application.

To create a resource controller, use the `make:controller` command with the `--resource` option:

```bash
php titan make:controller PhotoController --resource
```

This command will generate a controller at `app/Controllers/PhotoController.php` with methods for each of the available resource operations.

Next, you may register a resourceful route to the controller:

```php
Route::resource('photos', 'PhotoController');
```

This single route declaration creates multiple routes to handle a variety of actions on the resource. The generated controller will already have methods stubbed for each of these actions, including notes informing you of the HTTP verbs and URIs they handle.

The following table lists the actions handled by a resource controller, the verbs they respond to, and the corresponding controller methods:

| Verb      | URI                    | Action       | Route Name     |
|-----------|------------------------|--------------|----------------|
| GET       | /photos                | index        | photos.index   |
| GET       | /photos/create         | create       | photos.create  |
| POST      | /photos                | store        | photos.store   |
| GET       | /photos/{photo}        | show         | photos.show    |
| GET       | /photos/{photo}/edit   | edit         | photos.edit    |
| PUT/PATCH | /photos/{photo}        | update       | photos.update  |
| DELETE    | /photos/{photo}        | destroy      | photos.destroy |

### Customizing Resource Routes

You can customize individual resource controller actions:

```php
Route::resource('photos', 'PhotoController')->only(['index', 'show']);
Route::resource('photos', 'PhotoController')->except(['create', 'store', 'update', 'destroy']);
```

### Nested Resources

Sometimes you may need to define routes to a "nested" resource. For example, a photo resource may have multiple "comments" that can be attached to the photo. To "nest" resource controllers, use "dot" notation in your route declaration:

```php
Route::resource('photos.comments', 'PhotoCommentController');
```

This route will register a nested resource that may be accessed with URIs like the following:

```
/photos/{photo}/comments/{comment}
```

### API Resource Routes

When declaring resource routes that will be consumed by APIs, you will commonly want to exclude routes that present HTML templates like `create` and `edit`. For convenience, you may use the `apiResource` method to automatically exclude these routes:

```php
Route::apiResource('photos', 'PhotoController');
```

You can also combine `apiResource` registrations into a single line:

```php
Route::apiResources([
    'photos' => 'PhotoController',
    'posts' => 'PostController',
]);
```

## Dependency Injection & Controllers

### Constructor Injection

Titan's service container automatically resolves dependencies for your controller's constructor. This is a powerful way to pass dependencies to your controller:

```php
<?php

namespace App\Controllers;

use App\Services\UserService;
use Titan\Http\Request;
use Titan\Http\Response;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function show(Request $request, $id): Response
    {
        $user = $this->userService->findUserById($id);

        return $this->view('users.show', [
            'user' => $user
        ]);
    }
}
```

### Method Injection

In addition to constructor injection, you can also type-hint dependencies in your controller's methods:

```php
<?php

namespace App\Controllers;

use App\Services\UserService;
use Titan\Http\Request;
use Titan\Http\Response;

class UserController extends Controller
{
    public function show(Request $request, UserService $userService, $id): Response
    {
        $user = $userService->findUserById($id);

        return $this->view('users.show', [
            'user' => $user
        ]);
    }
}
```

## Route Model Binding

When using route model binding, Titan automatically resolves route parameters that match type-hinted variables in your controller method:

```php
<?php

namespace App\Controllers;

use App\Models\User;
use Titan\Http\Request;
use Titan\Http\Response;

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

Titan will automatically inject the `User` model instance with an ID matching the route parameter. If a matching model is not found, a 404 error will be automatically generated.

## Best Practices

Here are some best practices for working with controllers in Titan:

1. **Keep controllers focused**: Controllers should handle HTTP requests and return responses. Put complex business logic in services or models.

2. **Use resource controllers**: For typical CRUD operations, use resource controllers to follow RESTful conventions.

3. **Validate input in controllers**: Controllers are responsible for validating input data before passing it to services or models.

4. **Use dependency injection**: Use constructor injection to pass dependencies to your controllers, making them more testable.

5. **Return appropriate responses**: Always return a `Response` object from your controller methods, using the helper methods provided by the base `Controller` class.

6. **Use model binding**: Whenever possible, use route model binding to automatically resolve route parameters to model instances.

7. **Keep methods short**: Keep controller methods short and focused on a single responsibility. If a method grows too large, consider extracting some of its logic to a service.

## Next Steps

Now that you have a basic understanding of controllers in Titan, you might want to explore related topics:

- [Request Handling](requests-responses.md)
- [Response Generation](requests-responses.md#responses)
- [Validation](validation.md)
- [Middleware](middleware.md)
- [Service Container](../architecture/service-container.md)
- [Route Model Binding](routing.md#route-model-binding)
