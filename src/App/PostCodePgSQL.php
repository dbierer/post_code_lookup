<?php
namespace App;

use PDO;
use RuntimeException;

/**
 * Creates and imports GeoNames postal code data.
 * This class is designed for PostgreSQL databases via the pdo_pgsql driver.
 *
 * Data format reference: ./data/readme.txt from the GeoNames postal code dump.
 */
class PostCodePgSQL extends PostCodeBase
{
    public function __construct(public array $config)
    {
        $databaseName = trim($config['db']['pgsql']['DB_NAME'] ?? '');

        if ($databaseName === '') {
            throw new RuntimeException('Missing required environment variable: DB_NAME');
        }

        $host     = $config['db']['pgsql']['DB_HOST'] ?? '127.0.0.1';
        $port     = $config['db']['pgsql']['DB_PORT'] ?? '5432';
        $username = $config['db']['pgsql']['DB_USER'] ?? 'db_admin';
        $password = $config['db']['pgsql']['DB_PASSWORD'] ?? 'db_password';
        $charset  = $config['db']['pgsql']['DB_CHARSET'] ?? 'UTF8';

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;options=--client_encoding=%s',
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
     * PostgreSQL requires double-quoted identifiers, not backticks.
     */
    protected function quoteIdentifier(string $identifier): string
    {
        return sprintf('"%s"', $identifier);
    }

    /**
     * Checks to see if TABLE exists
     */
    public function checkTable(): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables '
            . 'WHERE table_schema = current_schema() AND table_name = :table_name',
        );
        $statement->execute(['table_name' => self::TABLE_NAME]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Creates the GeoNames postcode table.
     *
     * The schema is based on the tab-delimited GeoNames postal code file layout.
     */
    public function createTable(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS "postcode" (
    "id" BIGSERIAL PRIMARY KEY,
    "country_code" CHAR(2) NOT NULL,
    "postal_code" VARCHAR(20) NOT NULL,
    "place_name" VARCHAR(180) NOT NULL,
    "admin_name1" VARCHAR(100) NULL,
    "admin_code1" VARCHAR(20) NULL,
    "admin_name2" VARCHAR(100) NULL,
    "admin_code2" VARCHAR(20) NULL,
    "admin_name3" VARCHAR(100) NULL,
    "admin_code3" VARCHAR(20) NULL,
    "latitude" NUMERIC(10, 7) NULL,
    "longitude" NUMERIC(11, 7) NULL,
    "accuracy" SMALLINT NULL
)
SQL;

        $this->pdo->exec($sql);
        $this->pdo->exec(
            'CREATE INDEX IF NOT EXISTS "idx_postcode_country_postal_code" ON "postcode" ("country_code", "postal_code")',
        );
        $this->pdo->exec(
            'CREATE INDEX IF NOT EXISTS "idx_postcode_postal_code" ON "postcode" ("postal_code")',
        );
        $this->pdo->exec(
            'CREATE INDEX IF NOT EXISTS "idx_postcode_place_name" ON "postcode" ("place_name")',
        );
    }
}
