<?php
// error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);
error_log(__DIR__ . '/../data/error.log');
// load classes
require __DIR__ . '/../src/autoload.php';
use App\PostCodeBase;
use App\PostCode;
use App\PostCodeSQLite;
use App\PostCodePgSQL;
$config = require(__DIR__ . '/../config/config.php');
$usage = 'php ' . basename(__FILE__) . ' [ISO2] [DRIVER]' . PHP_EOL
       . '    ISO2 : 2 character country code (default "US")' . PHP_EOL
       . '    DRIVER : mariadb | sqlite | pgsql (default from config/config.php)' . PHP_EOL;
try {
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('This script must be run from the command line.');
    }
    $driver = strtolower(trim(strip_tags($argv[2] ?? $config['db']['DRIVER'] ?? PostCodeBase::DEFAULT_DRIVER)));
    $postcode = PostCodeBase::getClass($driver, $config);
    $iso2 = trim(strtoupper(strip_tags($argv[1] ?? 'US')));
    $dataDir = __DIR__ . '/../data/';
    $filePath = $dataDir . strtoupper($iso2) . '.txt';
    if (!file_exists($filePath)) {
        throw new RuntimeException('Geonames postcode data file ' . $filePath . ' not found.');
    }
    echo 'Checking to see if table exists...' . PHP_EOL;
    if (empty($postcode->checkTable())) {
        $create = readline('Do you want to create a new table? Y/n ');
        if (strtoupper($create) === 'Y') {
            echo 'Creating postcode table...' . PHP_EOL;
            $postcode->createTable();
        } else {
            echo 'Table does not exist and user declined to create a new table.';
            exit(1);
        }
    }
    echo sprintf('Importing GeoNames postcode data from %s...', $filePath) . PHP_EOL;
    $insertedRows = $postcode->importFromFile($filePath, (int) ($config['BATCH_SIZE'] ?? 1_000));
    $msg = sprintf('Import complete. Inserted %d rows into postcode.', $insertedRows);
    $num = 0;
} catch (PDOException $e) {
    $msg = $usage . 'Database error';
    error_log(__FILE__ . ':' . sprintf('Database error: %s%s', $e->getMessage(), PHP_EOL));
    $num = 1;
} catch (RuntimeException $e) {
    $msg = $usage . $e->getMessage();
    error_log(__FILE__ . ':' . sprintf('Runtime error: %s%s', $e->getMessage(), PHP_EOL));
    $num = 1;
} catch (Throwable $e) {
    $msg = $usage . 'Unknown error';
    error_log(__FILE__ . ':' . sprintf('Import failed: %s%s', $e->getMessage(), PHP_EOL));
    $num = 1;
}
echo $msg . PHP_EOL;
exit($num);
