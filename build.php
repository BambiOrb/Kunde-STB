<?php
/**
 * STB Atelier – Build-Skript
 * --------------------------------------------------------
 * Fügt die Seiten aus src/pages/*.html mit den gemeinsamen Bausteinen aus
 * src/partials/*.html zusammen (Header, Footer, Script-Tags) und schreibt
 * das Ergebnis als ganz normale, statische .html-Dateien in die
 * Projektwurzel – dieselben Dateien wie bisher, die ihr wie gewohnt per
 * FTP hochladet. Am Deployment ändert sich nichts.
 *
 * Aufruf:
 *   php build.php
 *
 * Wann ausführen? Immer wenn etwas in src/ geändert wurde (z. B. Header,
 * Footer, Navigation) – danach wie gewohnt die veränderten .html-Dateien
 * aus der Projektwurzel per FTP hochladen.
 */

$root      = __DIR__;
$pagesDir  = $root . '/src/pages';
$partialsDir = $root . '/src/partials';

$includePattern = '/^<!--#include\s+(.+?)\s*-->\s*$/';

$pages = glob($pagesDir . '/*.html');
if (!$pages) {
    fwrite(STDERR, "Keine Seiten in src/pages gefunden.\n");
    exit(1);
}

$built = 0;
foreach ($pages as $pagePath) {
    $lines = file($pagePath, FILE_IGNORE_NEW_LINES);
    $out = [];

    foreach ($lines as $line) {
        if (preg_match($includePattern, $line, $m)) {
            $partialPath = $partialsDir . '/' . basename($m[1]);
            // basename() verhindert Pfad-Ausbrüche (../..) beim Einlesen der Partials.
            if (!is_file($partialPath)) {
                fwrite(STDERR, "Partial nicht gefunden: {$m[1]} (in " . basename($pagePath) . ")\n");
                exit(1);
            }
            $partial = rtrim(file_get_contents($partialPath), "\n");
            $out[] = $partial;
        } else {
            $out[] = $line;
        }
    }

    $target = $root . '/' . basename($pagePath);
    file_put_contents($target, implode("\n", $out) . "\n");
    $built++;
    echo "gebaut: " . basename($pagePath) . "\n";
}

echo "\n{$built} Seite(n) erzeugt.\n";

/* --- Optionaler Übersetzungs-Check (nur falls Node.js verfügbar ist) --- */
// Rein informativ: bricht den Build nie ab, auch nicht bei fehlenden Keys.
// Wer kein Node installiert hat, sieht diesen Abschnitt einfach nicht.
$nodeCheck = $root . '/tools/check-translations.js';
if (is_file($nodeCheck)) {
    $nodePath = trim((string) @shell_exec('command -v node 2>/dev/null'));
    if ($nodePath !== '') {
        echo "\n--- Übersetzungs-Check (translations.js) ---\n";
        echo shell_exec('node ' . escapeshellarg($nodeCheck) . ' 2>&1');
    }
}
