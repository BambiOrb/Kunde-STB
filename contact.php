<?php
/**
 * STB Atelier – Kontaktformular-Endpoint
 * Nimmt JSON oder klassisches POST entgegen, validiert,
 * speichert in data/messages.json und schickt optional eine Mail.
 */

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';

function respond($ok, $error = null) {
    echo json_encode($error ? ['success' => false, 'error' => $error] : ['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Methode nicht erlaubt.');
}

/* --- Eingaben lesen (JSON-Body oder Formular-POST) --- */
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$firstName = trim($data['firstName'] ?? '');
$lastName  = trim($data['lastName']  ?? '');
$email     = trim($data['email']     ?? '');
$message   = trim($data['message']   ?? '');

/* --- Validierung --- */
if ($firstName === '' || $lastName === '' || $email === '' || $message === '') {
    http_response_code(422);
    respond(false, 'Bitte alle Felder ausfüllen.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    respond(false, 'Bitte eine gültige E-Mail-Adresse angeben.');
}
if (mb_strlen($message) > 5000) {
    http_response_code(422);
    respond(false, 'Nachricht ist zu lang.');
}

/* --- Datensatz aufbauen --- */
$entry = [
    'id'        => bin2hex(random_bytes(6)),
    'firstName' => htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'),
    'lastName'  => htmlspecialchars($lastName,  ENT_QUOTES, 'UTF-8'),
    'email'     => htmlspecialchars($email,     ENT_QUOTES, 'UTF-8'),
    'message'   => htmlspecialchars($message,   ENT_QUOTES, 'UTF-8'),
    'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
    'created'   => date('c'),
];

/* --- In JSON-Datei speichern (mit Lock) --- */
$file = $config['data_file'];
$dir  = dirname($file);
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}

$fp = @fopen($file, 'c+');
if ($fp === false) {
    http_response_code(500);
    respond(false, 'Speichern nicht möglich.');
}
if (flock($fp, LOCK_EX)) {
    $contents = stream_get_contents($fp);
    $list = json_decode($contents, true);
    if (!is_array($list)) $list = [];
    $list[] = $entry;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
}
fclose($fp);

/* --- Benachrichtigungs-Mail (optional, scheitert leise) --- */
if (!empty($config['notify_email'])) {
    $subject = '[' . $config['site_name'] . '] Neue Kontaktanfrage';
    $body  = "Name: {$firstName} {$lastName}\n";
    $body .= "E-Mail: {$email}\n\n";
    $body .= "Nachricht:\n{$message}\n";
    $headers = 'From: no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'stbatelier.ch') . "\r\n";
    $headers .= 'Reply-To: ' . $email . "\r\n";
    @mail($config['notify_email'], $subject, $body, $headers);
}

respond(true);
