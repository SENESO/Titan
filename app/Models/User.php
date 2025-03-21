<?php

namespace App\Models;

/**
 * User Model
 *
 * Represents a user in the application.
 */
class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected string $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected array $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Find a user by their email address.
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail(string $email): ?self
    {
        $instance = new static;
        $table = $instance->getTable();

        $query = "SELECT * FROM {prefix}$table WHERE email = :email LIMIT 1";
        $result = $instance->getConnection()->select($query, ['email' => $email]);

        if (empty($result)) {
            return null;
        }

        return new static($result[0]);
    }

    /**
     * Check if the given password matches the user's password.
     *
     * @param string $password
     * @return bool
     */
    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->getAttribute('password'));
    }

    /**
     * Set the user's password (hashes it automatically).
     *
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->setAttribute('password', password_hash($password, PASSWORD_BCRYPT));

        return $this;
    }

    /**
     * Generate a remember token for the user.
     *
     * @return string
     */
    public function generateRememberToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->setAttribute('remember_token', $token);

        return $token;
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->getAttribute('name');
    }

    /**
     * Get the user's initials.
     *
     * @return string
     */
    public function getInitials(): string
    {
        $name = $this->getFullName();
        $parts = explode(' ', $name);

        $initials = '';
        foreach ($parts as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return substr($initials, 0, 2);
    }
}
