<?php

namespace App\Models;

use PDO;
use JsonSerializable;
use Titan\Database\Connection;

/**
 * Base Model
 *
 * This is the base model class that all models in the application should extend.
 * It provides basic ORM functionality for interacting with the database.
 */
abstract class Model implements JsonSerializable
{
    /**
     * The database connection instance.
     *
     * @var Connection|null
     */
    protected static ?Connection $connection = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected string $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Indicates if the model should automatically manage timestamps.
     *
     * @var bool
     */
    protected bool $timestamps = true;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected array $hidden = [];

    /**
     * Create a new model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute from the model.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param string $key
     * @return bool
     */
    public function isFillable(string $key): bool
    {
        return in_array($key, $this->fillable);
    }

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set the database connection instance.
     *
     * @param Connection $connection
     * @return void
     */
    public static function setConnection(Connection $connection): void
    {
        static::$connection = $connection;
    }

    /**
     * Get the database connection instance.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        if (!static::$connection) {
            throw new \RuntimeException('Database connection not set.');
        }

        return static::$connection;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get the primary key value.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getPrimaryKey());
    }

    /**
     * Find a model by its primary key.
     *
     * @param mixed $id
     * @return static|null
     */
    public static function find($id): ?self
    {
        $instance = new static;
        $table = $instance->getTable();
        $primaryKey = $instance->getPrimaryKey();

        $query = "SELECT * FROM {prefix}$table WHERE $primaryKey = :id LIMIT 1";
        $result = $instance->getConnection()->select($query, ['id' => $id]);

        if (empty($result)) {
            return null;
        }

        return new static($result[0]);
    }

    /**
     * Get all records from the database.
     *
     * @return array
     */
    public static function all(): array
    {
        $instance = new static;
        $table = $instance->getTable();

        $query = "SELECT * FROM {prefix}$table";
        $results = $instance->getConnection()->select($query);

        $models = [];
        foreach ($results as $result) {
            $models[] = new static($result);
        }

        return $models;
    }

    /**
     * Save the model to the database.
     *
     * @return bool
     */
    public function save(): bool
    {
        $attributes = $this->getAttributes();

        // Add timestamps if enabled
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            if (empty($this->getKey())) {
                $attributes['created_at'] = $now;
            }
            $attributes['updated_at'] = $now;
        }

        if (empty($this->getKey())) {
            return $this->performInsert($attributes);
        }

        return $this->performUpdate($attributes);
    }

    /**
     * Perform an insert operation.
     *
     * @param array $attributes
     * @return bool
     */
    protected function performInsert(array $attributes): bool
    {
        $table = $this->getTable();
        $primaryKey = $this->getPrimaryKey();

        $columns = array_keys($attributes);
        $placeholders = array_map(function ($column) {
            return ":$column";
        }, $columns);

        $columnsString = implode(', ', $columns);
        $placeholdersString = implode(', ', $placeholders);

        $query = "INSERT INTO {prefix}$table ($columnsString) VALUES ($placeholdersString)";
        $id = $this->getConnection()->insert($query, $attributes);

        if ($id) {
            $this->setAttribute($primaryKey, $id);
            return true;
        }

        return false;
    }

    /**
     * Perform an update operation.
     *
     * @param array $attributes
     * @return bool
     */
    protected function performUpdate(array $attributes): bool
    {
        $table = $this->getTable();
        $primaryKey = $this->getPrimaryKey();
        $id = $this->getKey();

        $sets = [];
        foreach ($attributes as $column => $value) {
            if ($column !== $primaryKey) {
                $sets[] = "$column = :$column";
            }
        }

        $setsString = implode(', ', $sets);
        $query = "UPDATE {prefix}$table SET $setsString WHERE $primaryKey = :$primaryKey";

        $attributes[$primaryKey] = $id;
        $affected = $this->getConnection()->update($query, $attributes);

        return $affected > 0;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $table = $this->getTable();
        $primaryKey = $this->getPrimaryKey();
        $id = $this->getKey();

        if (!$id) {
            return false;
        }

        $query = "DELETE FROM {prefix}$table WHERE $primaryKey = :id";
        $affected = $this->getConnection()->delete($query, ['id' => $id]);

        return $affected > 0;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $key
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Convert the model to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->getAttributes();

        // Remove hidden attributes
        foreach ($this->hidden as $hidden) {
            unset($attributes[$hidden]);
        }

        return $attributes;
    }

    /**
     * Serialize the model instance.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
    }
}
