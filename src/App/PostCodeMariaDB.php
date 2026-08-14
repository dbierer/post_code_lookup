<?php
namespace App;

use PDO;
use RuntimeException;
use SplFileObject;
use Throwable;

/**
 * Creates and imports GeoNames postal code data.
 * This class is designed for MariaDB or MySQL databases.
 *
 * Data format reference: ./data/readme.txt from the GeoNames postal code dump.
 */
class PostCodeMariaDB extends PostCodeBase
{
    public string $db_name = '';
    public function __construct(public array $config)
    {
        $this->db_name = trim($config['db']['mariadb']['DB_NAME'] ?? '');

        if ($this->db_name === '') {
            throw new RuntimeException('Missing required environment variable: DB_NAME');
        }

        $host      = $config['db']['mariadb']['DB_HOST'] ?? '127.0.0.1';
        $port      = $config['db']['mariadb']['DB_PORT'] ?? '3306';
        $username  = $config['db']['mariadb']['DB_USER'] ?? 'db_admin';
        $password  = $config['db']['mariadb']['DB_PASSWORD'] ?? 'db_password';
        $charset   = $config['db']['mariadb']['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $this->db_name,
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
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables '
            . 'WHERE table_schema = :table_schema AND table_name = :table_name',
        );
        $stmt->execute([
            'table_schema' => $this->db_name,
            'table_name' => self::TABLE_NAME,
        ]);
        $result = (int) $stmt->fetchColumn() > 0;
        unset($stmt);
        return (bool) $result;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $this->pdo->exec($sql);
    }

}
