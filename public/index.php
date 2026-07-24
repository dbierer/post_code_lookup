<?php
require __DIR__ . '/../src/autoload.php';
use App\PostCodeBase;
use App\PostCode;
use App\PostCodeSQLite;
$config = require __DIR__ . '/../config/config.php';

$fieldLabels = [
    'id' => 'ID',
    'country_code' => 'Country Code',
    'postal_code' => 'Postal Code',
    'place_name' => 'City',
    'admin_name1' => 'State/Province',
    'admin_code1' => 'State/Province Code',
    'admin_name2' => 'County/Province',
    'admin_code2' => 'County/Province Code',
    'admin_name3' => 'Community',
    'admin_code3' => 'Community Code',
    'latitude' => 'Latitude',
    'longitude' => 'Longitude',
    'accuracy' => 'Accuracy',
];

$results = [];
$error = null;
$searchedField = 'postal_code';
$searchedValue = '';
$secondSearchedField = '';
$secondSearchedValue = '';
$logicalOperator = 'AND';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchedField = trim(strip_tags((string) ($_POST['field_name'] ?? '')));
    $searchedValue = trim(strip_tags((string) ($_POST['search_value'] ?? '')));
    $secondSearchedField = trim(strip_tags((string) ($_POST['field_name_2'] ?? '')));
    $secondSearchedValue = trim(strip_tags((string) ($_POST['search_value_2'] ?? '')));
    $logicalOperator = strtoupper(trim(strip_tags((string) ($_POST['logical_operator'] ?? 'AND'))));

    if (!in_array($logicalOperator, ['AND', 'OR'], true)) {
        $logicalOperator = 'AND';
    }

    if (!array_key_exists($searchedField, $fieldLabels)) {
        $error = 'Select a valid postcode data field.';
    } elseif ($searchedValue === '') {
        $error = 'Enter a lookup value.';
    } elseif ($secondSearchedField !== '' && !array_key_exists($secondSearchedField, $fieldLabels)) {
        $error = 'Select a valid additional postcode data field.';
    } elseif ($secondSearchedField !== '' && $secondSearchedValue === '') {
        $error = 'Enter an additional lookup value.';
    } else {
        try {
            $driver = strtolower(trim($config['db']['DRIVER'] ?? 'mysql'));
            $postcode = PostCodeBase::getClass($driver);
            $results = $postCode->lookup(
                $searchedField,
                $searchedValue,
                $secondSearchedField !== '' ? $secondSearchedField : null,
                $secondSearchedField !== '' ? $secondSearchedValue : null,
                $logicalOperator,
            );
        } catch (Throwable $throwable) {
            $error = $throwable->getMessage();
        }
    }
}

function escapeHtml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GeoNames Postcode Lookup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.5;
            margin: 2rem;
        }

        form {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-bottom: 2rem;
            max-width: 1100px;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        input,
        select {
            box-sizing: border-box;
            padding: 0.5rem;
            width: 100%;
        }

        .actions,
        .message,
        .results {
            grid-column: 1 / -1;
        }

        button {
            padding: 0.6rem 1rem;
        }

        .error {
            background: #ffecec;
            border: 1px solid #cc0000;
            color: #900;
            padding: 1rem;
        }

        .message {
            background: #eef6ff;
            border: 1px solid #8cbce8;
            padding: 1rem;
        }

        table {
            border-collapse: collapse;
            font-size: 0.9rem;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 0.4rem;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <h1>GeoNames Postcode Lookup</h1>

    <p>Select a GeoNames postcode data field and enter a value to search for.</p>

    <?php if ($error !== null) : ?>
        <div class="error"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="row">
            <div class="col">
                <label for="field_name">Lookup Field</label>
                <select id="field_name" name="field_name">
                    <?php foreach ($fieldLabels as $fieldName => $label) : ?>
                        <option value="<?= escapeHtml($fieldName) ?>" <?= $searchedField === $fieldName ? 'selected' : '' ?>>
                            <?= escapeHtml($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="search_value">Lookup Value</label>
                <input type="text" id="search_value" name="search_value" value="<?= escapeHtml($searchedValue) ?>" />
            </div>
            <div class="col">
                <label for="logical_operator">Combine With</label>
                <select id="logical_operator" name="logical_operator">
                    <option value="AND" <?= $logicalOperator === 'AND' ? 'selected' : '' ?>>AND</option>
                    <option value="OR" <?= $logicalOperator === 'OR' ? 'selected' : '' ?>>OR</option>
                </select>
            </div>

            <div class="col">
                <label for="field_name_2">Additional Lookup Field (optional)</label>
                <select id="field_name_2" name="field_name_2">
                    <option value="" <?= $secondSearchedField === '' ? 'selected' : '' ?>>&mdash; None &mdash;</option>
                    <?php foreach ($fieldLabels as $fieldName => $label) : ?>
                        <option value="<?= escapeHtml($fieldName) ?>" <?= $secondSearchedField === $fieldName ? 'selected' : '' ?>>
                            <?= escapeHtml($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="search_value_2">Additional Lookup Value (optional)</label>
                <input type="text" id="search_value_2" name="search_value_2" value="<?= escapeHtml($secondSearchedValue) ?>" />
            </div>
        </div>

        <div class="actions">
            <button type="submit">Lookup</button>
            <button type="reset">Reset</button>
        </div>
    </form>

    <section class="results" aria-live="polite">
        <h2>Lookup Results</h2>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) : ?>
            <div class="message">
                Lookup by <?= escapeHtml($fieldLabels[$searchedField] ?? (string) $searchedField) ?>
                for &ldquo;<?= escapeHtml($searchedValue) ?>&rdquo;
                <?php if ($secondSearchedField !== '') : ?>
                    <?= escapeHtml($logicalOperator) ?>
                    <?= escapeHtml($fieldLabels[$secondSearchedField] ?? (string) $secondSearchedField) ?>
                    for &ldquo;<?= escapeHtml($secondSearchedValue) ?>&rdquo;
                <?php endif; ?>
                returned <?= count($results) ?> result(s).
            </div>
        <?php endif; ?>

        <?php if ($results !== []) : ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($results[0]) as $columnName) : ?>
                            <th><?= escapeHtml($columnName) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <?php foreach ($row as $value) : ?>
                                <td><?= escapeHtml($value) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) : ?>
            <p>No matching postcode records were found.</p>
        <?php else : ?>
            <p>Submit the form to display postcode lookup results here.</p>
        <?php endif; ?>
    </section>
</body>
</html>
