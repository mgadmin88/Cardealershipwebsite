<?php
/**
 * فایل پیکربندی — این فایل را با نام config.php کپی کنید (نصب‌کننده خودکار این کار را انجام می‌دهد).
 * هرگز این فایل را در پوشه public قرار ندهید.
 */
return [
    'debug'    => false,                 // در محیط واقعی حتماً false
    'timezone' => 'Asia/Tehran',
    'base_url' => '',                    // مثال: https://example.com  (خالی = تشخیص خودکار)
    'force_https' => false,              // پس از نصب SSL روی true بگذارید
    'app_key'  => '__APP_KEY__',         // کلید تصادفی امنیتی (توسط نصب‌کننده ساخته می‌شود)

    'db' => [
        'driver'  => 'sqlite',           // sqlite یا mysql
        'path'    => __DIR__ . '/storage/database.sqlite',
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'autosite',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'upload' => [
        'max_size_mb' => 8,
        'max_width'   => 1920,           // تصاویر بزرگ‌تر به این عرض کوچک می‌شوند
    ],
];
