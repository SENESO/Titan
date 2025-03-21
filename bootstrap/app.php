<?php

/**
 * Titan Framework - Bootstrap File
 *
 * This file creates the application instance and bootstraps the framework.
 */

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
