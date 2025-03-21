<?php

namespace Titan\Database;

use PDO;
use PDOException;
use Titan\Database\Exception\DatabaseException;

/**
 * Class Connection
 *
 * Database connection wrapper built on PDO.
 */
class Connection
{
    /**
     * The active PDO connection.
     *
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * The default fetch mode.
     *
     * @var int
     */
    protected int $fetchMode = PDO::FETCH_ASSOC;

    /**
     * The connection configuration.
     *
     * @var array
     */
    protected array $config;

    /**
     * The database connection name.
     *
     * @var string
     */
    protected string $name;

    /**
     * The table prefix.
     *
     * @var string
     */
    protected string $tablePrefix;

    /**
     * The database driver name.
     *
     * @var string
     */
    protected string $driverName;

    /**
     * Create a new database connection instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // Extract connection name and table prefix
        $this->name = $config['name'] ?? 'default';
        $this->tablePrefix = $config['prefix'] ?? '';

        // Create the connection
        $this->connect();
    }

    /**
     * Create a new connection instance.
     *
     * @return PDO
     *
     * @throws DatabaseException
     */
    protected function connect(): PDO
    {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => $this->fetchMode,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Merge with user-provided options if any
            if (isset($this->config['options']) && is_array($this->config['options'])) {
                $options = array_merge($options, $this->config['options']);
            }

            // Create DSN based on driver
            $dsn = $this->getDsn();

            // Connect
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                $options
            );

            $this->driverName = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            // Set encoding
            if ($this->driverName === 'mysql' && isset($this->config['charset'])) {
                $charset = $this->config['charset'];
                $collation = $this->config['collation'] ?? 'utf8_unicode_ci';

                $this->pdo->prepare("SET NAMES '$charset' COLLATE '$collation'")->execute();
            }

            return $this->pdo;
        } catch (PDOException $e) {
            throw new DatabaseException('Could not connect to database: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create the DSN for the connection.
     *
     * @return string
     *
     * @throws DatabaseException
     */
    protected function getDsn(): string
    {
        if (!isset($this->config['driver'])) {
            throw new DatabaseException('Database driver not specified.');
        }

        $driver = $this->config['driver'];

        switch ($driver) {
            case 'mysql':
                return $this->getMysqlDsn();
            case 'pgsql':
                return $this->getPostgresDsn();
            case 'sqlite':
                return $this->getSqliteDsn();
            case 'sqlsrv':
                return $this->getSqlServerDsn();
            default:
                throw new DatabaseException("Unsupported database driver: $driver");
        }
    }

    /**
     * Get MySQL DSN string.
     *
     * @return string
     */
    protected function getMysqlDsn(): string
    {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? '3306';
        $database = $this->config['database'];

        return "mysql:host=$host;port=$port;dbname=$database";
    }

    /**
     * Get PostgreSQL DSN string.
     *
     * @return string
     */
    protected function getPostgresDsn(): string
    {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? '5432';
        $database = $this->config['database'];

        return "pgsql:host=$host;port=$port;dbname=$database";
    }

    /**
     * Get SQLite DSN string.
     *
     * @return string
     */
    protected function getSqliteDsn(): string
    {
        $database = $this->config['database'];

        return "sqlite:$database";
    }

    /**
     * Get SQL Server DSN string.
     *
     * @return string
     */
    protected function getSqlServerDsn(): string
    {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? '1433';
        $database = $this->config['database'];

        return "sqlsrv:Server=$host,$port;Database=$database";
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function select(string $query, array $bindings = []): array
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);

            return $statement->fetchAll($this->fetchMode);
        });
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function insert(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);

            return (int) $this->pdo->lastInsertId();
        });
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function update(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);

            return $statement->rowCount();
        });
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function delete(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);

            return $statement->rowCount();
        });
    }

    /**
     * Execute a statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->pdo->prepare($query);

            return $statement->execute($bindings);
        });
    }

    /**
     * Run a raw, unprepared query against the database.
     *
     * @param string $query
     * @return bool
     */
    public function unprepared(string $query): bool
    {
        return $this->run($query, [], function ($query) {
            return (bool) $this->pdo->exec($query);
        });
    }

    /**
     * Run a database query against the connection.
     *
     * @param string $query
     * @param array $bindings
     * @param callable $callback
     * @return mixed
     *
     * @throws DatabaseException
     */
    protected function run(string $query, array $bindings, callable $callback)
    {
        try {
            // Replace table prefixes
            $query = $this->replaceTablePrefixes($query);

            return $callback($query, $bindings);
        } catch (PDOException $e) {
            throw new DatabaseException(
                'Database query failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Replace table prefixes in the given query.
     *
     * @param string $query
     * @return string
     */
    protected function replaceTablePrefixes(string $query): string
    {
        return preg_replace('/\{prefix\}/', $this->tablePrefix, $query);
    }

    /**
     * Begin a new database transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the active database transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback the active database transaction.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Get the PDO instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get the database connection name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the table prefix.
     *
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Set the default fetch mode.
     *
     * @param int $fetchMode
     * @return $this
     */
    public function setFetchMode(int $fetchMode): self
    {
        $this->fetchMode = $fetchMode;
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $fetchMode);

        return $this;
    }
}
