<?php
namespace App;

use PDO;
use RuntimeException;
use SplFileObject;
use Throwable;

/**
 * Creates and imports GeoNames postal code data into a SQLite3 database.
 *
 * Data format reference: ./data/readme.txt from the GeoNames postal code dump.
 */
class PostCodeSQLite extends PostCodeBase
{
    public function __construct(public array $config)
    {
        $databasePath = trim($config['db']['DB_SQLITE_PATH'] ?? '');

        if ($databasePath === '') {
            throw new RuntimeException('Missing required environment variable: DB_SQLITE_PATH');
        }

        $dsn = sprintf('sqlite:%s', $databasePath);

        $this->pdo = new PDO(
            $dsn,
            null,
            null,
            [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
        );
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    /**
     * Checks to see if TABLE exists
     */
    public function checkTable(): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = :table_name",
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
CREATE TABLE IF NOT EXISTS `postcode` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `country_code` TEXT NOT NULL,
    `postal_code` TEXT NOT NULL,
    `place_name` TEXT NOT NULL,
    `admin_name1` TEXT NULL,
    `admin_code1` TEXT NULL,
    `admin_name2` TEXT NULL,
    `admin_code2` TEXT NULL,
    `admin_name3` TEXT NULL,
    `admin_code3` TEXT NULL,
    `latitude` REAL NULL,
    `longitude` REAL NULL,
    `accuracy` INTEGER NULL
)
SQL;

        $this->pdo->exec($sql);
        $this->pdo->exec(
            'CREATE INDEX IF NOT EXISTS `idx_postcode_country_postal_code` ON `postcode` (`country_code`, `postal_code`)',
        );
        $this->pdo->exec(
            'CREATE INDEX IF NOT EXISTS `idx_postcode_postal_code` ON `postcode` (`postal_code`)',
        );
        $this->pdo->exec(
            'CREATE INDEX IF NOT EXISTS `idx_postcode_place_name` ON `postcode` (`place_name`)',
        );
    }

}
