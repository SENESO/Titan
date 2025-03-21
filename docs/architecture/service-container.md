# Service Container

The Service Container is one of the most powerful features of the Titan Framework. It's a sophisticated dependency injection container that manages class dependencies and performs dependency injection.

## Introduction

The service container is essentially a registry where you define how to create your application's objects. This makes your code more maintainable, testable, and decoupled.

In Titan, the service container is represented by the `Titan\Container\Container` class, which implements the PSR-11 `ContainerInterface`. The main application class, `Titan\Core\Application`, extends this container, so you can use the application instance as a container.

## Basic Usage

### Binding

The most basic way to use the container is to bind an interface to a concrete implementation:

```php
$container->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);
```

Now, whenever your application needs an implementation of `PaymentGatewayInterface`, the container will instantiate `StripePaymentGateway`.

You can also bind a concrete implementation to an abstract name:

```php
$container->bind('payment.gateway', StripePaymentGateway::class);
```

### Binding a Singleton

Sometimes, you may want to bind a class as a singleton, which means the container will only instantiate the object once, and return the same instance on subsequent calls:

```php
$container->singleton(PaymentGatewayInterface::class, StripePaymentGateway::class);
```

### Binding an Instance

You can also bind an existing instance to the container:

```php
$paymentGateway = new StripePaymentGateway('api-key');
$container->instance(PaymentGatewayInterface::class, $paymentGateway);
```

### Binding a Closure

You can bind a closure to the container, which will be executed when the container needs to resolve the binding:

```php
$container->bind('payment.gateway', function ($container) {
    return new StripePaymentGateway(
        $container->make(ApiClient::class)
    );
});
```

### Making/Resolving

To resolve a binding from the container, you can use the `make` method:

```php
$paymentGateway = $container->make(PaymentGatewayInterface::class);
```

Or, if you've bound to an abstract name:

```php
$paymentGateway = $container->make('payment.gateway');
```

## Automatic Resolution

One of the powerful features of Titan's service container is automatic resolution. The container can automatically resolve classes without any explicit binding, as long as the class doesn't have unresolvable dependencies.

For example, if you have a class `UserController` that depends on a `UserRepository`:

```php
class UserController
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
}
```

You can resolve it without any explicit binding:

```php
$userController = $container->make(UserController::class);
```

The container will automatically create a `UserRepository` instance and inject it into the `UserController`.

## Contextual Binding

Sometimes, you may have two classes that depend on the same interface, but you want to inject different implementations. For this, you can use contextual binding:

```php
$container->when(UserController::class)
          ->needs(PaymentGatewayInterface::class)
          ->give(StripePaymentGateway::class);

$container->when(AdminController::class)
          ->needs(PaymentGatewayInterface::class)
          ->give(PayPalPaymentGateway::class);
```

Now, when the container resolves `UserController`, it will inject a `StripePaymentGateway`, and when it resolves `AdminController`, it will inject a `PayPalPaymentGateway`.

## Tagging

The container also supports tagging, which allows you to tag related bindings:

```php
$container->bind(StripePaymentGateway::class, StripePaymentGateway::class);
$container->bind(PayPalPaymentGateway::class, PayPalPaymentGateway::class);

$container->tag([
    StripePaymentGateway::class,
    PayPalPaymentGateway::class,
], 'payment.gateways');
```

You can then retrieve all tagged bindings:

```php
$paymentGateways = $container->tagged('payment.gateways');
```

## Extending Resolved Objects

The container allows you to extend a resolved object with additional functionality:

```php
$container->extend(PaymentGatewayInterface::class, function ($paymentGateway, $container) {
    $paymentGateway->setLogger($container->make(LoggerInterface::class));

    return $paymentGateway;
});
```

Now, whenever the container resolves `PaymentGatewayInterface`, it will pass the resolved object to the callback, which can modify it before it's returned.

## Container Events

The container fires events at various points in the resolution lifecycle, which allows you to hook into the resolution process:

```php
$container->beforeResolving(function ($abstract, $parameters) {
    // Called before resolving any type
});

$container->resolving(function ($object, $container) {
    // Called when resolving any type
});

$container->afterResolving(function ($object, $container) {
    // Called after resolving any type
});
```

You can also register callbacks for specific types:

```php
$container->beforeResolving(UserController::class, function ($userController, $container) {
    // Called before resolving UserController
});

$container->resolving(UserController::class, function ($userController, $container) {
    // Called when resolving UserController
});

$container->afterResolving(UserController::class, function ($userController, $container) {
    // Called after resolving UserController
});
```

## Service Providers

Service providers are the central place to configure your application. They're responsible for binding services into the container, registering event listeners, and performing any other setup your application requires.

To learn more about service providers, see the [Service Providers](service-providers.md) documentation.

## PSR-11 Compliance

Titan's service container is compliant with the PSR-11 container interface, which means it can be used with any library that requires a PSR-11 container:

```php
if ($container->has(PaymentGatewayInterface::class)) {
    $paymentGateway = $container->get(PaymentGatewayInterface::class);
}
```

## Performance Considerations

Titan's service container is optimized for performance. It caches resolved objects, so subsequent calls to `make` or `get` for the same binding will return the cached instance, without re-executing the resolution process.

However, if you're resolving the same binding many times within a tight loop, it might be more efficient to resolve it once and store it in a local variable:

```php
$paymentGateway = $container->make(PaymentGatewayInterface::class);

foreach ($payments as $payment) {
    $paymentGateway->process($payment);
}
```

## Advanced Container Usage

The service container has many more advanced features, such as:

- Binding interfaces to implementations
- Binding multiple implementations to an interface
- Conditional binding based on the environment
- Method injection in addition to constructor injection
- Recursive dependency resolution

For more detailed information, see the [Advanced Container](../advanced/container.md) documentation.

## Next Steps

Now that you understand the basics of Titan's service container, you might want to learn about:

- [Service Providers](service-providers.md)
- [Facades](facades.md)
- [Advanced Container Usage](../advanced/container.md)
