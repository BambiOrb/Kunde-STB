<?php
/**
 * STB Atelier – Bild-Report & WebP-Generierung
 * --------------------------------------------------------
 * Einmaliges Werkzeug (kein Teil des Deployments):
 *  1) Listet Breite/Höhe aller referenzierten Bilder (für width/height
 *     in den <img>-Tags, gegen Layout-Sprünge/CLS).
 *  2) Erzeugt für grosse Fotos (>150 KB) eine .webp-Variante daneben,
 *     ohne das Original zu verändern oder zu löschen – die Seiten nutzen
 *     danach <picture> mit WebP + Original als Fallback.
 *
 * Aufruf: php tools/optimize-images.php
 */

$root = dirname(__DIR__);
$listFile = $root . '/tools/images-to-convert.txt';
if (!is_file($listFile)) {
    fwrite(STDERR, "tools/images-to-convert.txt fehlt.\n");
    exit(1);
}

$files = array_filter(array_map('trim', file($listFile)));

echo str_pad('Datei', 34) . str_pad('Maße', 12) . str_pad('Original', 10) . str_pad('WebP', 10) . "Ersparnis\n";
echo str_repeat('-', 78) . "\n";

$totalBefore = 0;
$totalAfter = 0;

foreach ($files as $rel) {
    $path = $root . '/' . $rel;
    if (!is_file($path)) {
        echo "FEHLT: {$rel}\n";
        continue;
    }
    $info = getimagesize($path);
    if (!$info) {
        echo "Kein Bild: {$rel}\n";
        continue;
    }
    [$w, $h, $type] = $info;
    $sizeBefore = filesize($path);

    $img = match ($type) {
        IMAGETYPE_PNG  => imagecreatefrompng($path),
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        default => null,
    };
    if (!$img) {
        echo str_pad($rel, 34) . str_pad("{$w}x{$h}", 12) . str_pad(number_format($sizeBefore / 1024, 0) . 'K', 10) . "– (Format nicht unterstützt)\n";
        continue;
    }

    imagepalettetotruecolor($img);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    $webpPath = preg_replace('/\.(png|jpe?g)$/i', '.webp', $path);
    imagewebp($img, $webpPath, 82);
    imagedestroy($img);

    $sizeAfter = filesize($webpPath);
    $totalBefore += $sizeBefore;
    $totalAfter += $sizeAfter;
    $saved = $sizeBefore > 0 ? round((1 - $sizeAfter / $sizeBefore) * 100) : 0;

    echo str_pad($rel, 34)
        . str_pad("{$w}x{$h}", 12)
        . str_pad(number_format($sizeBefore / 1024, 0) . 'K', 10)
        . str_pad(number_format($sizeAfter / 1024, 0) . 'K', 10)
        . "-{$saved}%\n";
}

echo str_repeat('-', 78) . "\n";
echo 'Gesamt: ' . number_format($totalBefore / 1024 / 1024, 2) . 'MB -> '
    . number_format($totalAfter / 1024 / 1024, 2) . 'MB'
    . ' (-' . round((1 - $totalAfter / max($totalBefore, 1)) * 100) . "%)\n";
