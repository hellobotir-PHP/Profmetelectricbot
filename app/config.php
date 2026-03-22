<?php

$envPath = dirname(__DIR__, 3) . '/env/.env';

if (!is_file($envPath)) {
    throw new RuntimeException(".env file topilmadi: {$envPath}");
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES);

foreach ($lines as $line) {
    $line = trim($line);

    // empty yoki comment
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    // "export KEY=VALUE" bo‘lsa
    if (str_starts_with($line, 'export ')) {
        $line = trim(substr($line, 7));
    }

    // '=' bo‘lmasa skip
    if (!str_contains($line, '=')) {
        continue;
    }

    [$name, $value] = explode('=', $line, 2);
    $name  = trim($name);
    $value = trim($value);

    // Inline comment: VALUE # comment (faqat qo‘shtirnoqsiz bo‘lsa)
    if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
        $value = preg_replace('/\s+#.*$/', '', $value);
        $value = trim($value);
    }

    // Quote'larni olib tashlash
    if (
        (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
        $value = substr($value, 1, -1);
    }

    $_ENV[$name] = $value;
    putenv($name . '=' . $value); // ixtiyoriy, lekin foydali
}

$pdo = new PDO(
    "mysql:host=".$_ENV['DB_HOST'].";dbname=".$_ENV['DB_NAME'].";charset=utf8mb4",
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);