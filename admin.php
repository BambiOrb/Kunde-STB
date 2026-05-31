<?php
/**
 * STB Atelier – Admin
 * Einfaches, passwortgeschütztes Dashboard für eingegangene Kontaktanfragen.
 * Login-Daten siehe config.php (Default: admin / stb-admin-2026).
 */

session_start();
$config = require __DIR__ . '/config.php';

/* ---------- Logout ---------- */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

/* ---------- Login ---------- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === $config['admin_user'] && password_verify($pass, $config['admin_hash'])) {
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
    } else {
        $error = 'Falscher Benutzername oder Passwort.';
    }
}

$authed = !empty($_SESSION['auth']);

/* ---------- Nachricht löschen ---------- */
if ($authed && isset($_POST['delete'])) {
    $id = $_POST['delete'];
    $file = $config['data_file'];
    if (is_file($file)) {
        $list = json_decode(file_get_contents($file), true) ?: [];
        $list = array_values(array_filter($list, fn($m) => ($m['id'] ?? '') !== $id));
        file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: admin.php');
    exit;
}

/* ---------- Daten laden ---------- */
$messages = [];
if ($authed && is_file($config['data_file'])) {
    $messages = json_decode(file_get_contents($config['data_file']), true) ?: [];
    $messages = array_reverse($messages); // neueste zuerst
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · STB Atelier</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600&family=Hanken+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{--bg:#0d0d0f;--surface:#1a1a20;--text:#f3efe8;--muted:#a39d92;--gold:#c9a24b;--line:rgba(255,255,255,.1)}
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'Hanken Grotesk',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
  h1,h2,th{font-family:'Oswald',sans-serif;text-transform:uppercase;letter-spacing:.06em}
  a{color:var(--gold)}
  .topbar{display:flex;justify-content:space-between;align-items:center;padding:20px 28px;border-bottom:1px solid var(--line)}
  .brand{font-family:'Oswald',sans-serif;letter-spacing:.16em;font-size:1.1rem}
  .brand span{color:var(--gold)}
  .wrap{max-width:1080px;margin:0 auto;padding:36px 24px}
  /* login */
  .login{max-width:380px;margin:10vh auto;background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:36px}
  .login h1{font-size:1.5rem;margin-bottom:6px}
  .login p{color:var(--muted);font-size:.9rem;margin-bottom:24px}
  label{display:block;font-family:'Oswald',sans-serif;text-transform:uppercase;letter-spacing:.1em;font-size:.7rem;color:var(--muted);margin:14px 0 6px}
  input{width:100%;background:#0f0f12;border:1px solid var(--line);border-radius:10px;padding:12px 14px;color:var(--text);font-family:inherit}
  input:focus{outline:none;border-color:var(--gold)}
  .btn{margin-top:22px;width:100%;background:var(--gold);color:#1b1408;border:0;border-radius:100px;padding:13px;
    font-family:'Oswald',sans-serif;text-transform:uppercase;letter-spacing:.1em;cursor:pointer;font-size:.85rem}
  .err{color:#e0816f;font-size:.88rem;margin-top:14px}
  /* table */
  .count{color:var(--muted);margin-bottom:24px}
  table{width:100%;border-collapse:collapse;background:var(--surface);border:1px solid var(--line);border-radius:14px;overflow:hidden}
  th,td{text-align:left;padding:14px 16px;border-bottom:1px solid var(--line);vertical-align:top;font-size:.92rem}
  th{font-size:.72rem;color:var(--muted);background:#15151a}
  tr:last-child td{border-bottom:0}
  td .meta{color:var(--muted);font-size:.8rem}
  .msg{max-width:420px;white-space:pre-wrap}
  .del{background:none;border:1px solid var(--line);color:var(--muted);border-radius:8px;padding:6px 10px;cursor:pointer;font-size:.78rem}
  .del:hover{border-color:#e0816f;color:#e0816f}
  .empty{text-align:center;color:var(--muted);padding:60px 0}
</style>
</head>
<body>

<?php if (!$authed): ?>
  <form class="login" method="post">
    <h1>STB <span style="color:var(--gold)">Admin</span></h1>
    <p>Bitte einloggen, um Kontaktanfragen zu sehen.</p>
    <label for="user">Benutzer</label>
    <input type="text" id="user" name="user" autocomplete="username" required>
    <label for="password">Passwort</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
    <button class="btn" type="submit">Login</button>
    <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  </form>

<?php else: ?>
  <div class="topbar">
    <div class="brand"><b>STB</b> <span>Admin</span></div>
    <div><a href="index.html">Website ansehen</a> &nbsp;·&nbsp; <a href="?logout=1">Logout</a></div>
  </div>

  <div class="wrap">
    <h2 style="font-size:1.6rem;margin-bottom:6px">Kontaktanfragen</h2>
    <p class="count"><?= count($messages) ?> Nachricht(en)</p>

    <?php if (empty($messages)): ?>
      <div class="empty">Noch keine Anfragen eingegangen.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr><th>Datum</th><th>Name</th><th>E-Mail</th><th>Nachricht</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($messages as $m): ?>
            <tr>
              <td class="meta"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($m['created'] ?? 'now'))) ?></td>
              <td><?= htmlspecialchars(($m['firstName'] ?? '') . ' ' . ($m['lastName'] ?? '')) ?></td>
              <td><a href="mailto:<?= htmlspecialchars($m['email'] ?? '') ?>"><?= htmlspecialchars($m['email'] ?? '') ?></a></td>
              <td class="msg"><?= htmlspecialchars($m['message'] ?? '') ?></td>
              <td>
                <form method="post" onsubmit="return confirm('Diese Nachricht löschen?')">
                  <input type="hidden" name="delete" value="<?= htmlspecialchars($m['id'] ?? '') ?>">
                  <button class="del" type="submit">Löschen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>

</body>
</html>
