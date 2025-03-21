# Models

Models in Titan Framework represent data and business logic in your application. They are the place where you interact with the database, define relationships between data, and implement domain-specific functionality.

## Introduction

Titan models provide a clean, elegant, and strongly-typed way to interact with your database. Each model corresponds to a table in your database and serves as a repository for all application logic related to that data.

## Defining Models

To create a model, you should extend the base `Model` class:

```php
<?php

namespace App\Models;

class User extends Model
{
    // Model implementation
}
```

By default, models will use the snake_case plural form of the class name as the table name. For example, the `User` model will use the `users` table. If you want to specify a different table name, you can set the `$table` property:

```php
<?php

namespace App\Models;

class User extends Model
{
    protected string $table = 'custom_users_table';
}
```

## Model Properties

### Primary Key

By default, Titan models use `id` as the primary key. You can specify a different primary key by setting the `$primaryKey` property:

```php
<?php

namespace App\Models;

class User extends Model
{
    protected string $primaryKey = 'user_id';
}
```

### Timestamps

By default, Titan models expect the database table to have `created_at` and `updated_at` timestamp columns. These columns are automatically managed when creating or updating models. To disable this feature, set the `$timestamps` property to `false`:

```php
<?php

namespace App\Models;

class User extends Model
{
    protected bool $timestamps = false;
}
```

### Mass Assignment Protection

By default, all model attributes can be mass-assigned. To protect against mass assignment vulnerabilities, you should define the `$fillable` or `$guarded` properties:

```php
<?php

namespace App\Models;

class User extends Model
{
    // Only these attributes can be mass-assigned
    protected array $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

Alternatively, if you want to guard certain attributes from mass assignment:

```php
<?php

namespace App\Models;

class User extends Model
{
    // These attributes cannot be mass-assigned
    protected array $guarded = [
        'id',
        'password',
        'remember_token',
    ];
}
```

### Hidden Attributes

When converting a model to an array or JSON, you may want to hide certain attributes, such as passwords:

```php
<?php

namespace App\Models;

class User extends Model
{
    protected array $hidden = [
        'password',
        'remember_token',
    ];
}
```

## Basic Usage

### Creating Models

To create a new model instance:

```php
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->password = bcrypt('password');
$user->save();
```

You can also use the `fill` method to populate multiple attributes at once:

```php
$user = new User();
$user->fill([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
]);
$user->save();
```

### Retrieving Models

To retrieve all records from a table:

```php
$users = User::all();
```

To retrieve a specific record by its primary key:

```php
$user = User::find(1);
```

If the record does not exist, `find` will return `null`. If you want to throw an exception when the record is not found, use `findOrFail`:

```php
$user = User::findOrFail(1);
```

### Updating Models

To update a model, retrieve it, change its attributes, and then call the `save` method:

```php
$user = User::find(1);
$user->name = 'New Name';
$user->save();
```

### Deleting Models

To delete a model, call the `delete` method on an instance:

```php
$user = User::find(1);
$user->delete();
```

You can also delete a model by its primary key:

```php
User::destroy(1);
User::destroy([1, 2, 3]);
```

## Query Building

Titan models provide a fluent interface for building database queries.

### Retrieving All Records

To retrieve all records from a model's table:

```php
$users = User::all();
```

### Adding Constraints

You can add constraints to your queries using various methods:

```php
$users = User::where('active', true)
             ->where('role', 'admin')
             ->orderBy('name')
             ->limit(10)
             ->get();
```

### First or Create

The `firstOrCreate` method attempts to find a record in the database that matches the given attributes. If the record cannot be found, a new record will be created with the attributes:

```php
$user = User::firstOrCreate([
    'email' => 'john@example.com'
], [
    'name' => 'John Doe',
    'password' => bcrypt('password')
]);
```

### Update or Create

The `updateOrCreate` method attempts to find a record in the database that matches the given attributes. If the record is found, it will be updated with the values from the attributes. If the record cannot be found, a new record will be created with the attributes:

```php
$user = User::updateOrCreate([
    'email' => 'john@example.com'
], [
    'name' => 'John Doe',
    'password' => bcrypt('password')
]);
```

## Relationships

One of the most powerful features of Titan's model system is the ability to define relationships between models. Titan supports several types of relationships:

### One to One

A one-to-one relationship exists when a model has exactly one related model. For example, a `User` model might have one `Profile`:

```php
<?php

namespace App\Models;

class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}
```

And the inverse of the relationship on the `Profile` model:

```php
<?php

namespace App\Models;

class Profile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### One to Many

A one-to-many relationship exists when a model has multiple related models. For example, a `User` model might have many `Post` models:

```php
<?php

namespace App\Models;

class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

And the inverse of the relationship on the `Post` model:

```php
<?php

namespace App\Models;

class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### Many to Many

A many-to-many relationship exists when multiple models can be related to multiple other models. For example, a `User` model might have many `Role` models, and each `Role` model might have many `User` models:

```php
<?php

namespace App\Models;

class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
```

And the inverse of the relationship on the `Role` model:

```php
<?php

namespace App\Models;

class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
```

The `belongsToMany` method assumes a pivot table with a naming convention of the singular form of the model names in alphabetical order. In this example, the pivot table would be `role_user`.

### Has Many Through

The "has-many-through" relationship provides a convenient shortcut for accessing distant relations via an intermediate relation. For example, if a `Country` model has many `User` models, and each `User` model has many `Post` models, then the `Country` model has many posts through users:

```php
<?php

namespace App\Models;

class Country extends Model
{
    public function posts()
    {
        return $this->hasManyThrough(Post::class, User::class);
    }
}
```

### Polymorphic Relationships

Polymorphic relationships allow a model to belong to more than one other model, using a single association. For example, you might have a `Comment` model that belongs to either a `Post` model or a `Video` model:

```php
<?php

namespace App\Models;

class Comment extends Model
{
    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Video extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
```

## Events

Titan models fire several events during their lifecycle, allowing you to hook into the following points in a model's lifecycle: `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`, `restoring`, and `restored`.

To listen for model events, you can define methods on your model:

```php
<?php

namespace App\Models;

class User extends Model
{
    protected static function booted()
    {
        static::created(function ($user) {
            // Handle the created event
        });
    }
}
```

## Custom Methods

You can add custom methods to your models to encapsulate domain logic:

```php
<?php

namespace App\Models;

class User extends Model
{
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

## Serialization

When building JSON APIs, you will often need to convert your models and relationships to arrays or JSON. Titan models include methods to make these conversions convenient:

```php
$user = User::with('posts')->find(1);

return $user->toArray();
```

You can also convert a model directly to JSON:

```php
$user = User::find(1);

return $user->toJson();
```

## Best Practices

Here are some best practices for working with models in Titan:

1. **Keep models focused**: Models should represent a single entity and its domain logic.

2. **Use meaningful names**: Name your models using clear, descriptive singular nouns (e.g., `User`, not `Users`).

3. **Define relationships explicitly**: Always define both sides of a relationship to make your code more readable and maintainable.

4. **Protect against mass assignment**: Always define `$fillable` or `$guarded` properties to protect against mass assignment vulnerabilities.

5. **Use type hints**: Take advantage of PHP 8.2+ type hints to make your code more robust.

6. **Encapsulate domain logic**: Use custom methods to encapsulate domain logic, keeping your controllers clean.

7. **Use model events judiciously**: While model events are powerful, overuse can lead to hidden side effects and make your code harder to understand.

## Next Steps

Now that you understand the basics of working with models in Titan, you might want to explore related topics:

- [Database: Getting Started](getting-started.md)
- [Query Builder](query-builder.md)
- [Relationships](relationships.md)
- [Migrations](migrations.md)
- [Seeding](seeding.md)
