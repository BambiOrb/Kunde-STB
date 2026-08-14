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

// Fehler, die bisher mit @ stillschweigend verschluckt wurden, landen jetzt
// wenigstens in data/error.log, statt spurlos zu verschwinden.
function log_error($message) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($dir . '/error.log', '[' . date('c') . '] ' . $message . "\n", FILE_APPEND | LOCK_EX);
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
$honeypot  = trim($data['website']   ?? ''); // Feld ist per CSS versteckt, nur Bots füllen es aus

/* --- Spam: Honeypot --- */
// Ausgefülltes Honeypot-Feld = (fast sicher) ein Bot. Wir antworten mit "success",
// speichern aber nichts, damit Bots keinen Hinweis bekommen, dass sie erkannt wurden.
if ($honeypot !== '') {
    respond(true);
}

/* --- Spam: einfaches Rate-Limit pro IP --- */
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if ($ip !== '') {
    $rateFile = $config['ratelimit_file'];
    $rateDir  = dirname($rateFile);
    if (!is_dir($rateDir) && !@mkdir($rateDir, 0755, true)) {
        log_error("Rate-Limit: Verzeichnis konnte nicht angelegt werden: {$rateDir}");
    }
    $rfp = @fopen($rateFile, 'c+');
    if ($rfp === false) {
        log_error("Rate-Limit: Datei konnte nicht geöffnet werden: {$rateFile}");
    }
    if ($rfp !== false) {
        if (flock($rfp, LOCK_EX)) {
            $raw   = stream_get_contents($rfp);
            $rates = json_decode($raw, true);
            if (!is_array($rates)) $rates = [];

            $ipKey = hash('sha256', $ip);
            $now   = time();
            $minInterval = 30; // Sekunden zwischen zwei Anfragen derselben IP

            // alte Einträge aufräumen (älter als 1h), damit die Datei nicht wächst
            $rates = array_filter($rates, fn($ts) => ($now - $ts) < 3600);

            if (isset($rates[$ipKey]) && ($now - $rates[$ipKey]) < $minInterval) {
                flock($rfp, LOCK_UN);
                fclose($rfp);
                http_response_code(429);
                respond(false, 'Bitte kurz warten, bevor du eine weitere Nachricht sendest.');
            }

            $rates[$ipKey] = $now;
            ftruncate($rfp, 0);
            rewind($rfp);
            fwrite($rfp, json_encode($rates));
            fflush($rfp);
            flock($rfp, LOCK_UN);
        }
        fclose($rfp);
    }
}

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

/* --- Datensatz aufbauen ---
 * Roh speichern, NICHT hier escapen: admin.php escaped beim Anzeigen
 * bereits (htmlspecialchars). Würden wir schon hier escapen, escaped
 * admin.php ein zweites Mal – z. B. "O'Brien" würde dann als
 * "O&amp;#039;Brien" angezeigt statt als "O'Brien". Escaping gehört an
 * die Ausgabe, nicht an die Eingabe. */
$entry = [
    'id'        => bin2hex(random_bytes(6)),
    'firstName' => $firstName,
    'lastName'  => $lastName,
    'email'     => $email,
    'message'   => $message,
    'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
    'created'   => date('c'),
];

/* --- In JSON-Datei speichern (mit Lock) --- */
$file = $config['data_file'];
$dir  = dirname($file);
if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
    log_error("Nachrichten-Verzeichnis konnte nicht angelegt werden: {$dir}");
}

$fp = @fopen($file, 'c+');
if ($fp === false) {
    log_error("Nachrichten-Datei konnte nicht geöffnet werden: {$file}");
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
    $headers = 'From: Kontaktformular STB Atelier <no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'stbatelier.ch') . ">\r\n";
    $headers .= 'Reply-To: ' . $email . "\r\n";
    if (!@mail($config['notify_email'], $subject, $body, $headers)) {
        log_error("Benachrichtigungs-Mail konnte nicht gesendet werden an {$config['notify_email']}");
    }
}

respond(true);
