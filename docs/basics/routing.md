# Routing

Routing is a fundamental aspect of web application development. Titan provides a clean, elegant routing API that makes it easy to define routes for your application.

## Basic Routing

The most basic Titan routes accept a URI and a closure, providing a simple and expressive method of defining routes:

```php
use Titan\Core\Facades\Route;

Route::get('/hello', function () {
    return 'Hello, World!';
});
```

### Available Router Methods

The router provides methods for all common HTTP verbs:

```php
Route::get($uri, $callback);
Route::post($uri, $callback);
Route::put($uri, $callback);
Route::patch($uri, $callback);
Route::delete($uri, $callback);
Route::options($uri, $callback);
```

You may sometimes need to register a route that responds to multiple HTTP verbs. You can use the `match` method:

```php
Route::match(['get', 'post'], '/form', function () {
    // Handle GET or POST requests...
});
```

Or, if you need to register a route that responds to all HTTP verbs, you can use the `any` method:

```php
Route::any('/api/endpoint', function () {
    // Handle all HTTP verbs...
});
```

## Route Parameters

Often, you will need to capture segments of the URI within your route. For example, you might need to capture a user's ID from the URL. You can do this by defining route parameters:

```php
Route::get('/users/{id}', function ($id) {
    return 'User '.$id;
});
```

You may define as many route parameters as needed:

```php
Route::get('/posts/{post}/comments/{comment}', function ($postId, $commentId) {
    // ...
});
```

### Optional Parameters

Sometimes you may need to specify a route parameter, but make the presence of that parameter optional. You can do this by placing a `?` mark after the parameter name:

```php
Route::get('/users/{name?}', function ($name = null) {
    return $name ? 'Hello, '.$name : 'Hello, Guest';
});

Route::get('/users/{name?}', function ($name = 'John') {
    return 'Hello, '.$name;
});
```

### Regular Expression Constraints

You can constrain the format of your route parameters using the `where` method:

```php
Route::get('/users/{id}', function ($id) {
    // ...
})->where('id', '[0-9]+');

Route::get('/users/{name}', function ($name) {
    // ...
})->where('name', '[A-Za-z]+');

Route::get('/users/{id}/{name}', function ($id, $name) {
    // ...
})->where(['id' => '[0-9]+', 'name' => '[A-Za-z]+']);
```

## Named Routes

Named routes allow you to generate URLs or redirects for specific routes. You can specify a name for a route by chaining the `name` method onto the route definition:

```php
Route::get('/users/profile', function () {
    // ...
})->name('profile');
```

Once you have assigned a name to a route, you can use it to generate URLs or redirects:

```php
// Generating URLs...
$url = route('profile');

// Generating Redirects...
return redirect()->route('profile');
```

If the named route defines parameters, you can pass the parameters as the second argument to the `route` method:

```php
Route::get('/users/{id}/profile', function ($id) {
    // ...
})->name('profile');

$url = route('profile', ['id' => 1]);
```

## Route Groups

Route groups allow you to share route attributes, such as middleware or namespaces, across a large number of routes without having to define those attributes on each individual route.

### Middleware

To assign middleware to all routes within a group, you may use the `middleware` method before defining the group:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        // Requires authentication...
    });

    Route::get('/account', function () {
        // Requires authentication...
    });
});
```

### Prefixes

The `prefix` method may be used to prefix each route in the group with a given URI:

```php
Route::prefix('admin')->group(function () {
    Route::get('/users', function () {
        // Matches The "/admin/users" URL
    });

    Route::get('/posts', function () {
        // Matches The "/admin/posts" URL
    });
});
```

### Namespaces

Another common use-case for route groups is assigning the same controller namespace to a group of routes:

```php
Route::namespace('Admin')->group(function () {
    // Controllers Within The "App\Controllers\Admin" Namespace
    Route::get('/users', 'UserController@index');
});
```

### Combining Attributes

You can combine multiple route group attributes:

```php
Route::middleware(['auth'])
    ->prefix('admin')
    ->namespace('Admin')
    ->group(function () {
        // ...
    });
```

## Route Model Binding

Titan's route model binding provides a convenient way to automatically inject model instances into your routes. For example, instead of injecting a user's ID, you can inject the entire `User` model instance that matches the given ID.

### Implicit Binding

Titan automatically resolves route parameters that match type-hinted variables in your route closure or controller method. For example:

```php
Route::get('/users/{user}', function (User $user) {
    return $user->name;
});
```

In this example, since the `$user` variable is type-hinted as the `User` model and the variable name matches the `{user}` route segment, Titan will automatically inject the `User` model instance that has an ID matching the corresponding value from the request URI.

If no matching model is found in the database, a 404 HTTP response will automatically be generated.

### Explicit Binding

If you want to use your own resolution logic, you can use explicit binding. To register an explicit binding, use the router's `model` method to specify the class for a given parameter:

```php
Route::model('user', User::class);
```

Next, define a route that contains a `{user}` parameter:

```php
Route::get('/users/{user}', function ($user) {
    return $user->name;
});
```

Since we have bound all `{user}` parameters to the `User` model, a `User` instance will be injected into the route. So, for example, a request to `users/1` will inject the `User` instance from the database which has an ID of `1`.

If a matching model instance is not found in the database, a 404 HTTP response will automatically be generated.

## Form Method Spoofing

HTML forms do not support `PUT`, `PATCH`, or `DELETE` actions. So, when defining `PUT`, `PATCH`, or `DELETE` routes that are called from an HTML form, you will need to add a hidden `_method` field to the form. The value sent with the `_method` field will be used as the HTTP request method:

```html
<form action="/users/1" method="POST">
    <input type="hidden" name="_method" value="PUT">
    <!-- ... -->
</form>
```

## Cross-Site Request Forgery (CSRF) Protection

Any HTML forms pointing to `POST`, `PUT`, `PATCH`, or `DELETE` routes that are defined in the `web` routes file should include a CSRF token field. Otherwise, the request will be rejected:

```html
<form action="/profile" method="POST">
    @csrf
    <!-- ... -->
</form>
```

The `@csrf` Blade directive generates the token field automatically.

## Accessing The Current Route

You can access information about the current route using the `Route` facade:

```php
$route = Route::current();
$name = Route::currentRouteName();
$action = Route::currentRouteAction();
```

## Fallback Routes

Using the `fallback` method, you can define a route that will be executed when no other route matches the incoming request:

```php
Route::fallback(function () {
    return 'Page Not Found';
});
```

The fallback route should always be the last route registered by your application.

## Rate Limiting

Titan provides a convenient way to rate limit access to your routes. For example, you might want to limit access to a group of routes to 60 attempts per minute:

```php
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/api/users', function () {
        // ...
    });
});
```

## Conclusion

This guide covered the basics of routing in Titan. Routing is a fundamental aspect of any web application, and Titan provides a clean, elegant API for defining routes. With features like named routes, route groups, route model binding, and more, Titan makes it easy to define and manage the routes in your application.

For more advanced routing features, such as route caching, subdomain routing, and route priorities, see the [Advanced Routing](../advanced/routing.md) documentation.
