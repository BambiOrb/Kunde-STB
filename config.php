<?php
/**
 * STB Atelier – Konfiguration
 * --------------------------------------------------------
 * WICHTIG: Vor dem Live-Schalten anpassen!
 *  - Admin-Passwort neu setzen (siehe unten)
 *  - Empfänger-E-Mail eintragen
 */

return [

    // Wohin sollen Kontaktanfragen per Mail gehen?
    'notify_email' => 'stbswiss@gmail.com',
    'site_name'    => 'STB Atelier',

    // Admin-Login für admin.php
    // Default-Login:  admin  /  stb-admin-2026
    // Neues Passwort erzeugen:
    //   php -r "echo password_hash('DEIN_PASSWORT', PASSWORD_DEFAULT);"
    // und den ausgegebenen Hash unten einsetzen.
    'admin_user' => 'admin',
    'admin_hash' => '$2b$10$Kk.8CZmHGZYlcujqpPzCoO3ufnjZ3kiMh7B2BhCAwQ41aM01WSZey', // = "stb-admin-2026" – bitte ändern!

    // Speicherort der eingegangenen Nachrichten
    'data_file' => __DIR__ . '/data/messages.json',
];
