<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Service\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
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

    private ?string $connectionName;
    private ManagerRegistry $manager;
    private ?LoggerInterface $logger;
    private ?Connection $db = null;

    /** @var array<DbService> */
    private array $servers = [];

    public function __construct(ManagerRegistry $manager, ?LoggerInterface $logger = null, ?string $connectionName = null)
    {
        $this->logger = $logger;
        $this->connectionName = $connectionName;
        $this->manager = $manager;
    }

    /**
     * @param string|null $connectionName
     * @return $this
     */
    public function server(string $connectionName = null): Dbservice
    {
        if ($this->connectionName == $connectionName) {
            return $this;
        }

        $cacheName = $connectionName ?: 'default';
        if (!isset($this->servers[$cacheName])) {
            $this->servers[$cacheName] = new DbService($this->manager, $this->logger, $connectionName);
        }

        return $this->servers[$cacheName];
    }

    /**
     * @return ManagerRegistry
     */
    public function getDoctrineManager(): ManagerRegistry
    {
        return $this->manager;
    }

    public function getEm(string $name = null): EntityManager
    {
        return $this->manager->getManager($name);
    }

    public function beginTransaction(): void
    {
        try {
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
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @return array|false False is returned if no rows are found.
     * @throws SqlDriverException
     */
    public function fetchAssociative(string $sql, array $params = [], array $types = []): array|false
    {
        $sql = $this->executeQuery($sql, $params, $types);

        try {
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
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return Result
     */
    public function executeQuery(string $sql, array $params = [], array $types = []): Result
    {
        try {
            return $this->db()->executeQuery($sql, $params, $types);
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $object The Object Class
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @return array
     * @throws SqlDriverException
     */
    public function fetchObjectAll(string $object, string $sql, array $params = [], array $types = []): array
    {
        if ($data = $this->fetchAllAssociative($sql, $params, $types)) {
            foreach ($data as &$item) {
                $item = $this->createObject($object, $item);
            }
        }

        return $data;
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string $sql    SQL query
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return array
     * @throws SqlDriverException
     */
    public function fetchAllAssociative(string $sql, array $params = [], array $types = []): array
    {
        $sql = $this->executeQuery($sql, $params, $types);
        try {
            return $sql->fetchAllAssociative();
        } catch (Exception\DriverException | Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    private function createObject(string $object, iterable $data = null): mixed
    {
        $result = new $object;
        foreach ($data as $k => $v) {
            $result->$k = $v;
        }

        return $result;
    }

    public function fetchObject(string $object, string $sql, array $params = [], array $types = [])
    {
        try {
            if ($item = $this->db()->fetchAssociative($sql, $params, $types)) {
                $item = $this->createObject($object, $item);
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
     * @param array  $params The prepared statement params.
     * @param array  $types  The query parameter types.
     * @return mixed False is returned if no rows are found.
     */
    public function fetchOne(string $sql, array $params = [], array $types = []): mixed
    {
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
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return array<int,mixed>
     * @throws SqlDriverException
     */
    public function fetchFirstColumn(string $sql, array $params = [], array $types = []): array
    {
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
     * @param       $sql
     * @param array $params Query parameters
     * @param array $types  Parameter types
     * @return array
     * @throws SqlDriverException
     */
    public function fetchAllAssociativeIndexed($sql, array $params = [], array $types = []): array
    {
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
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return Traversable
     * @throws SqlDriverException
     */
    public function iterateKeyValue(string $query, array $params = [], array $types = []): Traversable
    {
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
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return Traversable
     * @throws SqlDriverException
     */
    public function iterateAssociativeIndexed(string $query, array $params = [], array $types = []): Traversable
    {
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
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     * @return array The executed statement.
     * @deprecated use self::fetchAllKeyValue
     */
    public function fetchPairs(string $sql, array $params = [], array $types = []): array
    {
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

    public function fetchUniqIds($sql, array $params = [], array $types = []): array
    {
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
     * @param array  $params Statement parameters
     * @param array  $types  Parameter types
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function executeStatement(string $sql, array $params = [], array $types = []): int
    {
        try {
            return $this->db()->executeStatement($sql, $params, $types);
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
     * @param array  $types           Types of the inserted data.
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function insert(string $tableExpression, array $data, array $types = []): int
    {
        try {
            return $this->db()->insert($tableExpression, $data, $types);
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * @param null $seqName
     * @return string
     * @throws Exception
     */
    public function lastInsertId($seqName = null): string
    {
        return $this->db()->lastInsertId($seqName);
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to update quoted or unquoted.
     * @param array  $data            An associative array containing column-value pairs.
     * @param array  $identifier      The update criteria. An associative array containing column-value pairs.
     * @param array  $types           Types of the merged $data and $identifier arrays in that order.
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function update(string $tableExpression, array $data, array $identifier, array $types = []): int
    {
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
     * @param array  $types    Parameter types
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function delete(string $table, array $criteria, array $types = []): int
    {
        try {
            return $this->db()->delete($table, $criteria, $types);
        } catch (Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * @param        $table
     * @param array  $data
     * @param array  $cast
     * @param array  $conflict
     * @param array  $do
     * @param string $doWhere
     * @return bool|Result
     */
    public function upsert($table, array $data, array $cast = [], array $conflict = [], array $do = [], string $doWhere = ''): bool|Result
    {
        if (empty($data)) {
            return false;
        }

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

        $doStr = 'DO UPDATE SET ';
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
}
