<?php
/**
 * Einmaliges Reparatur-Skript: SA/RR/LC/KN/EM lagen als AVIF vor, aber mit
 * .jpg-Endung (falscher Content-Type, Browser konnten sie nicht anzeigen).
 * Nach git mv *.jpg -> *.avif hier: echten .jpg-Fallback aus dem AVIF
 * erzeugen, für Browser ohne AVIF-Unterstützung.
 */
$root = dirname(__DIR__);
$names = ['SA', 'RR', 'LC', 'KN', 'EM'];

foreach ($names as $name) {
    $avif = "{$root}/img/{$name}.avif";
    $jpg  = "{$root}/img/{$name}.jpg";
    if (!is_file($avif)) {
        echo "FEHLT: {$avif}\n";
        continue;
    }
    $img = imagecreatefromavif($avif);
    if (!$img) {
        echo "Konnte nicht dekodieren: {$avif}\n";
        continue;
    }
    imagepalettetotruecolor($img);
    imagejpeg($img, $jpg, 85);
    imagedestroy($img);
    echo "{$name}: " . round(filesize($avif) / 1024) . "K (avif) -> " . round(filesize($jpg) / 1024) . "K (jpg-Fallback)\n";
}
