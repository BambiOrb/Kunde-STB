<?php
/**
 * STB Atelier – Konfiguration
 * --------------------------------------------------------
 * WICHTIG: Zugangsdaten stehen NICHT mehr hier im Code.
 * Diese Datei liest sie aus zwei möglichen Quellen (in dieser Reihenfolge):
 *
 *   1) config.local.php  – lokale/produktive Overrides, NICHT im Git
 *      (siehe .gitignore). Vorlage: config.local.php.example
 *   2) Umgebungsvariablen STB_ADMIN_USER, STB_ADMIN_HASH, STB_NOTIFY_EMAIL
 *      – praktisch, wenn das Hosting kein config.local.php erlaubt.
 *
 * Einrichtung (einmalig, siehe auch README):
 *   cp config.local.php.example config.local.php
 *   php -r "echo password_hash('DEIN_PASSWORT', PASSWORD_DEFAULT);"
 *   → erzeugten Hash in config.local.php eintragen.
 *
 * Ist weder config.local.php noch eine Umgebungsvariable gesetzt, bleibt
 * der Admin-Login absichtlich deaktiviert (siehe admin.php) – es gibt
 * keinen eingebauten Default-Login mehr.
 */

$config = [
    'notify_email' => 'stbswiss@gmail.com',
    'site_name'    => 'STB Atelier',

    // Admin-Login für admin.php – standardmässig NICHT gesetzt.
    'admin_user' => null,
    'admin_hash' => null,

    // Speicherort der eingegangenen Nachrichten
    'data_file' => __DIR__ . '/data/messages.json',

    // Zustandsdatei für das Rate-Limiting des Kontaktformulars
    'ratelimit_file' => __DIR__ . '/data/ratelimit.json',
];

/* --- 1) lokale Datei (höchste Priorität) --- */
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        foreach ($local as $key => $value) {
            if ($value !== null && $value !== '') {
                $config[$key] = $value;
            }
        }
    }
}

/* --- 2) Umgebungsvariablen (falls noch nicht gesetzt) --- */
$envMap = [
    'admin_user'   => 'STB_ADMIN_USER',
    'admin_hash'   => 'STB_ADMIN_HASH',
    'notify_email' => 'STB_NOTIFY_EMAIL',
];
foreach ($envMap as $key => $envName) {
    $envValue = getenv($envName);
    if ($envValue !== false && $envValue !== '') {
        $config[$key] = $envValue;
    }
}

return $config;
