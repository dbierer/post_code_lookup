<?php
namespace App;

use PDO;
use RuntimeException;
use SplFileObject;
use Throwable;

/**
 * Creates and imports GeoNames postal code data.
 *
 * Data format reference: ./data/readme.txt from the GeoNames postal code dump.
 */
class PostCode
{
    public const TABLE_NAME = 'postcode';
    public const EXPECTED_FIELD_COUNT = 12;

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

    public function __construct(public array $config)
    {
        $databaseName = trim($config['db']['DB_NAME'] ?? '');

        if ($databaseName === '') {
            throw new RuntimeException('Missing required environment variable: DB_NAME');
        }

        $host      = $config['db']['DB_HOST'] ?? '127.0.0.1';
        $port      = $config['db']['DB_PORT'] ?? '3306';
        $username  = $config['db']['DB_USER'] ?? 'db_admin';
        $password  = $config['db']['DB_PASSWORD'] ?? 'db_password';
        $charset   = $config['db']['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $databaseName,
            $charset,
        );

        $this->pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Checks to see if TABLE exists
     */
    public function checkTable() : bool
    {
        return $this->pdo->exec('SELECT COUNT(*) FROM information_schema.tables '
                                . 'WHERE table_schema = DATABASE() AND table_name = :table_name');
    }

    /**
     * Creates the GeoNames postcode table.
     *
     * The schema is based on the tab-delimited GeoNames postal code file layout.
     */
    public function createTable(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `postcode` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `country_code` CHAR(2) NOT NULL,
    `postal_code` VARCHAR(20) NOT NULL,
    `place_name` VARCHAR(180) NOT NULL,
    `admin_name1` VARCHAR(100) NULL,
    `admin_code1` VARCHAR(20) NULL,
    `admin_name2` VARCHAR(100) NULL,
    `admin_code2` VARCHAR(20) NULL,
    `admin_name3` VARCHAR(100) NULL,
    `admin_code3` VARCHAR(20) NULL,
    `latitude` DECIMAL(10, 7) NULL,
    `longitude` DECIMAL(11, 7) NULL,
    `accuracy` TINYINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_postcode_country_postal_code` (`country_code`, `postal_code`),
    INDEX `idx_postcode_postal_code` (`postal_code`),
    INDEX `idx_postcode_place_name` (`place_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

        $this->pdo->exec($sql);
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

        $statement = $this->pdo->prepare(
            <<<'SQL'
INSERT INTO `postcode` (
    `country_code`,
    `postal_code`,
    `place_name`,
    `admin_name1`,
    `admin_code1`,
    `admin_name2`,
    `admin_code2`,
    `admin_name3`,
    `admin_code3`,
    `latitude`,
    `longitude`,
    `accuracy`
) VALUES (
    :country_code,
    :postal_code,
    :place_name,
    :admin_name1,
    :admin_code1,
    :admin_name2,
    :admin_code2,
    :admin_name3,
    :admin_code3,
    :latitude,
    :longitude,
    :accuracy
)
SQL
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
        $tableName = self::TABLE_NAME;

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
            sprintf('SELECT * FROM `%s` WHERE %s', $tableName, $whereClause),
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
        $columnName = $this->normalizeFieldName($fieldName);

        if ($searchData === null) {
            return [sprintf('`%s` IS NULL', $columnName), []];
        }

        return [sprintf('`%s` = :%s', $columnName, $paramName), [$paramName => $searchData]];
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
