<?php
namespace App;

use PDO;
use RuntimeException;
use SplFileObject;
use Throwable;

/**
 * Base class for GeoNames postal code data.
 *
 * Data format reference: ./data/readme.txt from the GeoNames postal code dump.
 */
abstract class PostCodeBase
{
    public const TABLE_NAME = 'postcode';
    public const EXPECTED_FIELD_COUNT = 12;
    public const DEFAULT_DRIVER = 'sqlite';
    /**
     * @var array<string, string>
     */
    public PDO $pdo;
    public const FIELD_NAMES = [
        'id' => 'id',
        'country_code' => 'country_code',
        'postal_code' => 'postal_code',
        'place_name' => 'place_name',
        'admin_name1' => 'admin_name1',
        'admin_code1' => 'admin_code1',
        'admin_name2' => 'admin_name2',
        'admin_code2' => 'admin_code2',
        'admin_name3' => 'admin_name3',
        'admin_code3' => 'admin_code3',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'accuracy' => 'accuracy',
    ];

    /**
     * Creates the GeoNames postcode table.
     *
     * The schema is based on the tab-delimited GeoNames postal code file layout.
     */
    public abstract function createTable(): void;
    
    /**
     * Checks to see if TABLE exists
     */
    public abstract function checkTable(): bool;

    /**
     * Returns the appropriate PostCode class based upon the value of "driver"
     *
     */
    public static function getClass(string $driver, array $config) : self
    {
        return match ($driver) {
            'sqlite', 'sqlite3' => new PostCodeSQLite($config),
            'mysql', 'mariadb' => new PostCodeMariaDB($config),
            'pgsql', 'postgres', 'postgresql' => new PostCodePgSQL($config),
            default => throw new RuntimeException(sprintf('Unsupported DB driver: %s', $driver)),
        };
    }

    /**
     * Quotes a table or column identifier for use in a raw SQL fragment.
     *
     * Backtick quoting works for MySQL/MariaDB/SQLite; PostgreSQL requires
     * double quotes, so PostCodePgSQL overrides this method.
     */
    protected function quoteIdentifier(string $identifier): string
    {
        return sprintf('`%s`', $identifier);
    }
    
    /**
     * Imports a GeoNames tab-delimited postal code data file.
     *
     * @return int Number of rows inserted.
     */
    public function importFromFile(string $filePath, int $batchSize = 1_000): int
    {
        if ($batchSize < 1) {
            throw new RuntimeException('Batch size must be greater than zero.');
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException(sprintf('GeoNames postcode file is not readable: %s', $filePath));
        }

        $insertColumns = [
            'country_code',
            'postal_code',
            'place_name',
            'admin_name1',
            'admin_code1',
            'admin_name2',
            'admin_code2',
            'admin_name3',
            'admin_code3',
            'latitude',
            'longitude',
            'accuracy',
        ];

        $statement = $this->pdo->prepare(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $this->quoteIdentifier(self::TABLE_NAME),
                implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $insertColumns)),
                implode(', ', array_map(static fn (string $column): string => ':' . $column, $insertColumns)),
            ),
        );

        $file = new SplFileObject($filePath, 'r');
        $insertedRows = 0;
        $rowsInBatch = 0;
        $ownsTransaction = !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            while (!$file->eof()) {
                $line = $file->fgets();

                if ($line === '') {
                    continue;
                }

                $line = rtrim($line, "\r\n");

                if ($line === '') {
                    continue;
                }

                $row = $this->parseLine($line, $file->key() + 1);

                $statement->execute($row);
                $insertedRows++;
                $rowsInBatch++;

                if ($ownsTransaction && $rowsInBatch >= $batchSize) {
                    $this->pdo->commit();
                    $this->pdo->beginTransaction();
                    $rowsInBatch = 0;
                }
            }

            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (Throwable $throwable) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }

        return $insertedRows;
    }

    /**
     * Looks up postcode rows by one of the table fields, optionally combined
     * with a second field/value pair using an AND or OR logical operator.
     *
     * The field name may use the database column name, for example `postal_code`,
     * or the GeoNames readme spelling, for example `postal code`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lookup(
        string $fieldName,
        mixed $searchData,
        ?string $secondFieldName = null,
        mixed $secondSearchData = null,
        string $logicalOperator = 'AND',
    ): array {
        $tableName = $this->quoteIdentifier(self::TABLE_NAME);

        [$whereClause, $bindings] = $this->buildCondition($fieldName, $searchData, 'search_data');

        if ($secondFieldName !== null && $secondFieldName !== '') {
            $normalizedOperator = strtoupper(trim($logicalOperator));

            if (!in_array($normalizedOperator, ['AND', 'OR'], true)) {
                throw new RuntimeException(sprintf('Invalid logical operator: %s', $logicalOperator));
            }

            [$secondCondition, $secondBindings] = $this->buildCondition(
                $secondFieldName,
                $secondSearchData,
                'search_data_2',
            );

            $whereClause = sprintf('(%s) %s (%s)', $whereClause, $normalizedOperator, $secondCondition);
            $bindings += $secondBindings;
        }

        $statement = $this->pdo->prepare(
            sprintf('SELECT * FROM %s WHERE %s', $tableName, $whereClause),
        );
        $statement->execute($bindings);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Builds a single `column = :param` (or `IS NULL`) condition for a lookup field.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    protected function buildCondition(string $fieldName, mixed $searchData, string $paramName): array
    {
        $columnName = $this->quoteIdentifier($this->normalizeFieldName($fieldName));

        if ($searchData === null) {
            return [sprintf('%s IS NULL', $columnName), []];
        }

        return [sprintf('%s = :%s', $columnName, $paramName), [$paramName => $searchData]];
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function parseLine(string $line, int $lineNumber): array
    {
        $fields = explode("\t", $line);

        if (count($fields) !== self::EXPECTED_FIELD_COUNT) {
            throw new RuntimeException(
                sprintf(
                    'Invalid GeoNames postcode data at line %d: expected %d fields, found %d.',
                    $lineNumber,
                    self::EXPECTED_FIELD_COUNT,
                    count($fields),
                ),
            );
        }

        return [
            'country_code' => $fields[0],
            'postal_code' => $fields[1],
            'place_name' => $fields[2],
            'admin_name1' => $this->emptyStringToNull($fields[3]),
            'admin_code1' => $this->emptyStringToNull($fields[4]),
            'admin_name2' => $this->emptyStringToNull($fields[5]),
            'admin_code2' => $this->emptyStringToNull($fields[6]),
            'admin_name3' => $this->emptyStringToNull($fields[7]),
            'admin_code3' => $this->emptyStringToNull($fields[8]),
            'latitude' => $this->emptyStringToNull($fields[9]),
            'longitude' => $this->emptyStringToNull($fields[10]),
            'accuracy' => $this->emptyStringToNullAsInt($fields[11]),
        ];
    }

    protected function normalizeFieldName(string $fieldName): string
    {
        $normalizedFieldName = strtolower(trim($fieldName));
        $normalizedFieldName = str_replace([' ', '-'], '_', $normalizedFieldName);

        if (!array_key_exists($normalizedFieldName, self::FIELD_NAMES)) {
            throw new RuntimeException(sprintf('Invalid postcode lookup field: %s', $fieldName));
        }

        return self::FIELD_NAMES[$normalizedFieldName];
    }

    protected function emptyStringToNull(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    protected function emptyStringToNullAsInt(string $value): ?int
    {
        return $value === '' ? null : (int) $value;
    }

}
