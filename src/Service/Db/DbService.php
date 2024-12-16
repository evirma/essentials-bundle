<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Service\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Evirma\Bundle\EssentialsBundle\Service\Db\Exception\SqlDriverException;
use Evirma\Bundle\EssentialsBundle\Traits\CacheTrait;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Traversable;

final class DbService
{
    use CacheTrait;

    /** @var array<string, DbService> */
    private array $servers = [];

    public static array $stat = [];

    private ?Connection $db = null;

    public function __construct(private readonly ManagerRegistry $manager, private ?LoggerInterface $logger = null, private readonly string $connectionName = 'default')
    {
    }

    public function server(string $connectionName = 'default'): DbService
    {
        if ($this->connectionName == $connectionName) {
            return $this;
        }

        $conn = $this->servers[$connectionName] ?? null;

        if ($conn instanceof DbService) {
            return $conn;
        }

        $dbService = new DbService($this->manager, $this->logger, $connectionName);
        $this->servers[$connectionName] = $dbService;
        return $dbService;
    }

    public function getDoctrineManager(): ManagerRegistry
    {
        return $this->manager;
    }

    public function getEm(?string $name = null): EntityManager
    {
        return $this->manager->getManager($name);
    }

    public function beginTransaction(): void
    {
        try {
            self::statInc('beginTransaction');
            $this->db()->beginTransaction();
        } catch (Exception $e) {
            $this->convertException($e);
        }
    }

    private function db(): Connection
    {
        if (!$this->db) {
            $this->db = $this->getConnection($this->connectionName);
        }

        return $this->db;
    }

    public function getConnection(?string $name = null): Connection
    {
        $connection = $this->manager->getConnection($name);

        if (!$connection instanceof Connection) {
            throw new RuntimeException('Connection Has Wrong Type');
        }

        return $connection;
    }

    public function commit(): void
    {
        try {
            self::statInc('commit');
            $this->db()->commit();
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    private function convertException(Throwable $e, $sql = null, array $params = [], array $types = []): SqlDriverException
    {
        $message = $e->getMessage();
        $message = preg_replace('#VALUES(.*?)ON\s+CONFLICT#usi', 'VALUES ({{VALUES}}) ON CONFLICT', $message);
        $message = preg_replace('#with params\s*\[.*?]#usi', 'with params [{{PARAMS}}]', $message);

        $exception = new SqlDriverException($message, $e);
        $exception->setExtra([
            'message' => $message,
            'connection' => $this->connectionName ?: 'default',
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ]);

        $this->logger?->error('SQL Execute Error', [
            'message' => $message,
            'connection' => $this->connectionName ?: 'default',
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
            'exception' => $exception,
        ]);

        return $exception;
    }

    public function rollBack(): void
    {
        try {
            self::statInc('rollBack');
            $this->db()->rollBack();
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $sql    The SQL query.
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     * @return array|false False is returned if no rows are found.
     * @throws SqlDriverException
     */
    public function fetchAssociative(string $sql, array $params = [], array $types = []): array|false
    {
        $sql = $this->executeQuery($sql, $params, $types);

        try {
            self::statInc('query');
            self::statInc('query__fetchAssociative');

            return $sql->fetchAssociative();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Executes an optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $sql    SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     * @return Result
     */
    public function executeQuery(string $sql, array $params = [], array $types = []): Result
    {
        try {
            return $this->db()->executeQuery($sql, $params, $types);
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @template T
     * @param class-string<T> $object The Object Class
     * @param string $sql    The SQL query.
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     * @return list<T>
     * @throws SqlDriverException
     */
    public function fetchObjectAll(string $object, string $sql, array $params = [], array $types = []): array
    {
        self::statInc('query');
        self::statInc('query__fetchObjectAll');

        $result = [];
        if ($data = $this->fetchAllAssociative($sql, $params, $types)) {
            foreach ($data as $item) {
                $result[] = $this->createObject($object, $item);
            }
        }

        return $result;
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string $sql    SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     * @return list<array<string,mixed>>
     * @throws SqlDriverException
     */
    public function fetchAllAssociative(string $sql, array $params = [], array $types = []): array
    {
        self::statInc('query');
        self::statInc('query__fetchAllAssociative');

        $sql = $this->executeQuery($sql, $params, $types);
        try {
            return $sql->fetchAllAssociative();
        } catch (Exception\DriverException | Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    private function createObject(string $object, ?iterable $data = null): mixed
    {
        $result = new $object;
        foreach ($data as $k => $v) {
            $result->$k = $v;
        }

        return $result;
    }

    /**
     * @template T
     * @param class-string<T> $object The Object Class
     * @param string $sql
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null>|null $types Parameter types
     *
     * @return ?T
     */
    public function fetchObject(string $object, string $sql, array $params = [], array $types = []): mixed
    {
        self::statInc('query');
        self::statInc('query__fetchObject');

        try {
            if ($item = $this->db()->fetchAssociative($sql, $params, $types)) {
                $item = $this->createObject($object, $item);
            } else {
                return null;
            }
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }

        return $item;
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string $sql    The SQL query to be executed.
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return mixed False is returned if no rows are found.
     */
    public function fetchOne(string $sql, array $params = [], array $types = []): mixed
    {
        self::statInc('query');
        self::statInc('query__fetchOne');

        if (!$sql = $this->executeQuery($sql, $params, $types)) {
            return false;
        }

        try {
            return $sql->fetchOne();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of the first column values.
     *
     * @param string $sql    SQL query
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return array<int,mixed>
     * @throws SqlDriverException
     */
    public function fetchFirstColumn(string $sql, array $params = [], array $types = []): array
    {
        self::statInc('query');
        self::statInc('query__fetchFirstColumn');

        if (preg_match("#COUNT\s*\(#usi", $sql)) {
            self::statInc('match__count');
        }

        if (preg_match("#\s*INSERT\s*\(#usi", $sql)) {
            self::statInc('match__insert');
        }

        if (preg_match("#\s*UPDATE\s*\(#usi", $sql)) {
            self::statInc('match__update');
        }

        if (preg_match("#\s*DELETE\s*\(#usi", $sql)) {
            self::statInc('match__delete');
        }

        $stmt = $this->executeQuery($sql, $params, $types);

        try {
            return $stmt->fetchFirstColumn();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * @param string                                           $sql    SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     * @return array
     * @throws SqlDriverException
     */
    public function fetchAllKeyValue(string $sql, array $params = [], array $types = []): array
    {
        self::statInc('query');
        self::statInc('query__fetchAllKeyValue');

        $stmt = $this->executeQuery($sql, $params, $types);

        try {
            return $stmt->fetchAllKeyValue();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param string $sql
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return array
     * @throws SqlDriverException
     */
    public function fetchAllAssociativeIndexed(string $sql, array $params = [], array $types = []): array
    {
        self::statInc('query');
        self::statInc('query__fetchAllAssociativeIndexed');

        $stmt = $this->executeQuery($sql, $params, $types);

        try {
            return $stmt->fetchAllAssociativeIndexed();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented
     * as associative arrays.
     *
     * @param string $query  SQL query
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return Traversable
     * @throws SqlDriverException
     */
    public function iterateKeyValue(string $query, array $params = [], array $types = []): Traversable
    {
        self::statInc('query');
        self::statInc('query__iterateKeyValue');

        $stmt = $this->executeQuery($query, $params, $types);

        try {
            return $stmt->iterateKeyValue();
        } catch (Exception $e) {
            throw $this->convertException($e, $query, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param string $query  SQL query
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return Traversable
     * @throws SqlDriverException
     */
    public function iterateAssociativeIndexed(string $query, array $params = [], array $types = []): Traversable
    {
        self::statInc('query');
        self::statInc('query__iterateAssociativeIndexed');

        $stmt = $this->executeQuery($query, $params, $types);

        try {
            return $stmt->iterateAssociativeIndexed();
        } catch (Exception $e) {
            throw $this->convertException($e, $query, $params, $types);
        }
    }

    /**
     * Executes an optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $sql    The SQL query to execute.
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return array The executed statement.
     * @deprecated use self::fetchAllKeyValue
     */
    public function fetchPairs(string $sql, array $params = [], array $types = []): array
    {
        self::statInc('query');
        self::statInc('query__fetchPairs');

        $sql = $this->executeQuery($sql, $params, $types);
        try {
            $data = $sql->fetchAllNumeric();
            if ($data) {
                $result = [];
                foreach ($data as $item) {
                    $result[$item[0]] = $item[1];
                }

                return $result;
            }
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }

        return [];
    }

    /**
     * @param string $sql
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     *
     * @return array
     */
    public function fetchUniqIds(string $sql, array $params = [], array $types = []): array
    {
        self::statInc('query');
        self::statInc('query__fetchUniqIds');

        $stmt = $this->executeQuery($sql, $params, $types);

        try {
            $data = $stmt->fetchAllNumeric();
            if ($data) {
                $result = [];
                foreach ($data as $item) {
                    $result[$item[0]] = $item[0];
                }

                return $result;
            }
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }

        return [];
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     * Could be used for:
     *  - DML statements: INSERT, UPDATE, DELETE, etc.
     *  - DDL statements: CREATE, DROP, ALTER, etc.
     *  - DCL statements: GRANT, REVOKE, etc.
     *  - Session control statements: ALTER SESSION, SET, DECLARE, etc.
     *  - Other statements that don't yield a row set.
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string $sql    SQL statement
     * @param list<mixed>|array<string, mixed> $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function executeStatement(string $sql, array $params = [], array $types = []): int
    {
        self::statInc('query');
        self::statInc('query__executeStatement');

        try {
            return (int)$this->db()->executeStatement($sql, $params, $types);
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Inserts a table row with specified data.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to insert data into, quoted or unquoted.
     * @param array  $data            An associative array containing column-value pairs.
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function insert(string $tableExpression, array $data, array $types = []): int
    {
        self::statInc('query');
        self::statInc('insert');

        try {
            return (int)$this->db()->insert($tableExpression, $data, $types);
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    public function lastInsertId(?string $seqName = null): string
    {
        self::statInc('query');
        self::statInc('lastInsertId');

        try {
            return (string)$this->db()->lastInsertId($seqName);
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to update quoted or unquoted.
     * @param array  $data            An associative array containing column-value pairs.
     * @param array  $identifier      The update criteria. An associative array containing column-value pairs.
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return int|string The number of affected rows.
     * @throws SqlDriverException
     */
    public function update(string $tableExpression, array $data, array $identifier, array $types = []): int|string
    {
        self::statInc('query');
        self::statInc('update');

        try {
            return $this->db()->update($tableExpression, $data, $identifier, $types);
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * Executes an SQL DELETE statement on a table.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $table    Table name
     * @param array  $criteria Deletion criteria
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function delete(string $table, array $criteria, array $types = []): int
    {
        self::statInc('query');
        self::statInc('delete');

        try {
            return (int)$this->db()->delete($table, $criteria, $types);
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    public function upsertBatched(int $batchSize, string $table, array $data, array $cast = [], array $conflict = [], array $do = [], string $doWhere = ''): void
    {
        $batchSize = max(100, $batchSize);
        $chunks = array_chunk($data, $batchSize);

        foreach ($chunks as $chunk) {
            $this->upsert($table, $chunk, $cast, $conflict, $do, $doWhere);
        }
    }

    public function upsert(string $table, array $data, array $cast = [], array $conflict = [], array $do = [], string $doWhere = ''): bool|Result
    {
        if (empty($data)) {
            return false;
        }

        self::statInc('query');
        self::statInc('upsert');

        $includeFields = array_keys($data[0]);
        $includeFieldsStr = implode(', ', $includeFields);

        [$values, $params] = $this->prepareMultipleValues($data, $includeFields, [], $cast);

        if ($values) {
            $conflictStr = empty($conflict) ? '' : ' (' . implode(',', $conflict) . ')';
            $doStr = empty($do) ? 'NOTHING' : $this->prepareDo($do, $doWhere);

            /** @noinspection SqlNoDataSourceInspection */
            $sql = "INSERT INTO $table ($includeFieldsStr) VALUES $values ON CONFLICT$conflictStr DO $doStr";

            return $this->executeQuery($sql, $params);
        }

        return true;
    }

    /**
     * Build Do construction like UPDATE field = EXCLUDED.fields
     *
     * @param array  $do
     * @param string $doWhere
     * @return string
     */
    protected function prepareDo(array $do = [], string $doWhere = ''): string
    {
        if (empty($do)) {
            return 'NOTHING';
        }

        $doStr = 'UPDATE SET ';
        foreach ($do as $key => $field) {
            if (is_int($key)) {
                $doStr .= "$field = EXCLUDED.$field, ";
            } else {
                $doStr .= "$key = $field, ";
            }
        }

        $doStr = rtrim($doStr, ', ');

        if ($doWhere) {
            $doStr .= ' WHERE ' . $doWhere;
        }

        return $doStr;
    }

    public function prepareMultipleValues(array $data, array $includeFields = [], array $excludeFields = [], array $cast = []): array
    {
        $sql = '';
        $params = [];
        $i = 0;
        $conn = $this->db();
        foreach ($data as $item) {
            $sqlValue = '';
            if (!empty($includeFields)) {
                uksort(
                    $item,
                    function ($a, $b) use ($includeFields) {
                        return array_search($a, $includeFields) <=> array_search($b, $includeFields);
                    }
                );
            }

            foreach ($item as $key => $value) {
                $isFieldIncluded = (!empty($includeFields) && in_array($key, $includeFields)) || empty($includeFields);
                $isFieldExcluded = (!empty($excludeFields) && in_array($key, $excludeFields));

                if ($isFieldIncluded && !$isFieldExcluded) {
                    $castType = $cast[$key] ?? '';

                    if (is_bool($value)) {
                        $value = $value ? 'TRUE' : 'FALSE';
                    }

                    $castTypeStr = $i ? '' : $castType;
                    if ($castType) {
                        if ($value == 'NULL') {
                            $sqlValue .= ", NULL";
                        } elseif ($castType === 'int[]') {
                            $sqlValue .= ', ARRAY [' . implode(',', array_map(static fn ($item) => (int)$item, $value)) . ']::integer[]';
                        } elseif ($castType === 'text[]') {
                            $sqlValue .= ', ARRAY [' . implode(',', array_map(static fn ($item) => $conn->quote($item, PDO::PARAM_STR), $value)) . ']::text[]';
                        } elseif ($castType != 'mixed') {
                            $sqlValue .= ",$castTypeStr ".$conn->quote($value, PDO::PARAM_STR);
                        } elseif (is_null($value)) {
                            $sqlValue .= ', NULL';
                        } else {
                            $sqlValue .= ', '.$conn->quote($value, PDO::PARAM_STR);
                        }
                    } else {
                        $sqlValue .= ", :".$key."__".$i;
                        $params[$key."__".$i] = $value;
                    }
                }
            }

            $sqlValue = ltrim($sqlValue, ', ');
            $sql .= ",\n($sqlValue)";
            $i++;
        }

        $sql = ltrim($sql, ', ');

        return [$sql, $params];
    }

    public function close(): void
    {
        $this->db()->close();
    }

    public function reconnect($tries = 5): bool
    {
        if (!$isConnected = $this->checkConnection()) {
            try {
                $this->db()->connect();
            } catch (Exception $e) {
                throw $this->convertException($e);
            }

            $isConnected = $this->checkConnection();
            if (--$tries <= 0) {
                return $isConnected;
            }

            if (!$isConnected) {
                sleep((6 - $tries) * 2);

                return $this->reconnect($tries);
            }
        }

        return $isConnected;
    }

    public function checkConnection(): bool
    {
        try {
            $this->db()->executeQuery("SELECT 1");

            return true;
        } catch (Exception) {
            return false;
        }
    }


    public function disableLogger(): DbService
    {
        $this->logger = null;

        return $this;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface|null $logger
     * @return DbService
     */
    public function setLogger(?LoggerInterface $logger): DbService
    {
        $this->logger = $logger;

        return $this;
    }

    private static function statInc(string $key): void
    {
        self::$stat[$key] = (self::$stat[$key] ?? 0) + 1;
    }

    public static function getStat(): array
    {
        return self::$stat;
    }


}
