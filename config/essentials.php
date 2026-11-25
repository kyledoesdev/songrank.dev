<?php

return [
    'timezone' => [
        'local_envs' => explode(',', (string) env('LOCAL_ENVS', 'local,development,dev')),
        'ttl' => (int) env('TIMEZONE_CACHE_TTL', 86400),
        'retry_after' => (int) env('TIMEZONE_RETRY_AFTER', 300),
    ],

    'db_backups' => [
        'enabled' => (bool) env('DB_BACKUP_ENABLED', false),
        'frequency' => env('DB_BACKUP_FREQUENCY') ?: '30 20 * * *',
        'timezone' => env('DB_BACKUP_TIMEZONE') ?: 'UTC',

        'connections' => array_values(array_filter(explode(',', (string) env('DB_BACKUP_CONNECTIONS', '')))),

        'disk' => env('DB_BACKUP_DISK'),
        's3' => [
            'key' => env('DB_BACKUP_AWS_ACCESS_KEY_ID') ?: env('AWS_ACCESS_KEY_ID'),
            'secret' => env('DB_BACKUP_AWS_SECRET_ACCESS_KEY') ?: env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('DB_BACKUP_AWS_DEFAULT_REGION') ?: env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('DB_BACKUP_AWS_BUCKET'),
            'endpoint' => env('DB_BACKUP_AWS_ENDPOINT'),
            'use_path_style_endpoint' => (bool) env('DB_BACKUP_AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        'directory' => env('DB_BACKUP_DIRECTORY', 'backups'),
        'folder_format' => env('DB_BACKUP_FOLDER_FORMAT', 'YmdHis'),
        'retention_days' => (int) env('DB_BACKUP_RETENTION_DAYS', 7),

        'log_channel' => env('DB_BACKUP_LOG_CHANNEL') ?: 'discord_other_updates',

        'temporary_directory' => env('DB_BACKUP_TEMPORARY_DIRECTORY', storage_path('app/essentials-backups')),

        'mysqldump' => [
            'binary' => env('DB_BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),
            'timeout' => (int) env('DB_BACKUP_TIMEOUT', 900),
        ],
    ],
];
