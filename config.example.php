<?php
// Optional local config fallback. Prefer environment variables on hosting.
// Never commit real credentials or config.php to GitHub.
return [
    'DB_HOST' => 'YOUR_SUPABASE_SESSION_POOLER_HOST',
    'DB_PORT' => '5432',
    'DB_NAME' => 'postgres',
    'DB_USER' => 'postgres.YOUR_PROJECT_REF',
    'DB_PASSWORD' => 'YOUR_DATABASE_PASSWORD',
    'DB_SSLMODE' => 'require',
    'SITE_NAME' => 'Hani Software',
    'WHATSAPP_NUMBER' => '84562812172',
];
