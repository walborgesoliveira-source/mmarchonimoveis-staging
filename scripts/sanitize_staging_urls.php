<?php

$replacements = [
    'https://dev.mmarchonimoveis.com.br' => 'https://staging.mmarchonimoveis.com.br',
    'https:\/\/dev.mmarchonimoveis.com.br' => 'https:\/\/staging.mmarchonimoveis.com.br',
    'wordpress@dev.mmarchonimoveis.com.br' => 'wordpress@staging.mmarchonimoveis.invalid',
    'dev.mmarchonimoveis.com.br' => 'staging.mmarchonimoveis.com.br',
];

$mysqli = new mysqli('db', 'mmarchon_user', 'senha_forte_123', 'mmarchon_staging');

if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}

$mysqli->set_charset('utf8mb4');

$replaceInString = function ($value) use ($replacements) {
    return str_replace(array_keys($replacements), array_values($replacements), $value);
};

$replaceRecursive = function ($value) use (&$replaceRecursive, $replaceInString) {
    if (is_string($value)) {
        return $replaceInString($value);
    }

    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = $replaceRecursive($item);
        }

        return $value;
    }

    if (is_object($value)) {
        foreach ($value as $key => $item) {
            $value->$key = $replaceRecursive($item);
        }
    }

    return $value;
};

$processSerializedColumn = function ($table, $idColumn, $valueColumn, $whereSql) use ($mysqli, $replaceInString, $replaceRecursive) {
    $result = $mysqli->query("SELECT {$idColumn} AS row_id, {$valueColumn} AS row_value FROM {$table} WHERE {$whereSql}");

    if (!$result) {
        throw new RuntimeException($mysqli->error);
    }

    while ($row = $result->fetch_assoc()) {
        $value = $row['row_value'];

        if (!is_string($value) || strpos($value, 'dev.mmarchonimoveis.com.br') === false) {
            continue;
        }

        $updated = $replaceInString($value);
        $unserialized = @unserialize($value);

        if ($unserialized !== false || $value === 'b:0;') {
            $updated = serialize($replaceRecursive($unserialized));
        }

        $stmt = $mysqli->prepare("UPDATE {$table} SET {$valueColumn} = ? WHERE {$idColumn} = ?");
        $rowId = (int) $row['row_id'];
        $stmt->bind_param('si', $updated, $rowId);
        $stmt->execute();
        $stmt->close();
    }

    $result->free();
};

$sqlUpdates = [
    "UPDATE wp_posts SET guid = REPLACE(guid, 'https://dev.mmarchonimoveis.com.br', 'https://staging.mmarchonimoveis.com.br') WHERE guid LIKE '%dev.mmarchonimoveis.com.br%'",
    "UPDATE wp_posts SET post_content = REPLACE(post_content, 'https://dev.mmarchonimoveis.com.br', 'https://staging.mmarchonimoveis.com.br') WHERE post_content LIKE '%dev.mmarchonimoveis.com.br%'",
    "UPDATE wp_users SET user_url = REPLACE(user_url, 'https://dev.mmarchonimoveis.com.br', 'https://staging.mmarchonimoveis.com.br') WHERE user_url LIKE '%dev.mmarchonimoveis.com.br%'",
];

foreach ($sqlUpdates as $sql) {
    if (!$mysqli->query($sql)) {
        throw new RuntimeException($mysqli->error);
    }
}

// Process options with string replacement to catch escaped JSON fragments as well.
$processSerializedColumn(
    'wp_options',
    'option_id',
    'option_value',
    "option_value LIKE '%dev.mmarchonimoveis.com.br%'"
);

$processSerializedColumn(
    'wp_postmeta',
    'meta_id',
    'meta_value',
    "meta_value LIKE '%dev.mmarchonimoveis.com.br%'"
);

$checks = [
    'options' => "SELECT COUNT(*) AS total FROM wp_options WHERE option_value LIKE '%dev.mmarchonimoveis.com.br%'",
    'post_guid' => "SELECT COUNT(*) AS total FROM wp_posts WHERE guid LIKE '%dev.mmarchonimoveis.com.br%'",
    'post_content' => "SELECT COUNT(*) AS total FROM wp_posts WHERE post_content LIKE '%dev.mmarchonimoveis.com.br%'",
    'users' => "SELECT COUNT(*) AS total FROM wp_users WHERE user_url LIKE '%dev.mmarchonimoveis.com.br%'",
    'postmeta' => "SELECT COUNT(*) AS total FROM wp_postmeta WHERE meta_value LIKE '%dev.mmarchonimoveis.com.br%'",
];

$result = [];

foreach ($checks as $label => $sql) {
    $query = $mysqli->query($sql);
    $result[$label] = (int) $query->fetch_assoc()['total'];
    $query->free();
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
