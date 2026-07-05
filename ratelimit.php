<?php
// Rate limiter (per-IP, sliding-window log). Datotečna shramba v .ratelimit/.
// Konfiguracija
const RATE_LIMIT_MAX     = 60;   // zahtevkov
const RATE_LIMIT_WINDOW  = 60;   // sekund

function rate_limit_check(): void {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

  // Samo produkcija: preskoči loopback (razen ko je izrecno vklopljeno za teste)
  $isLoopback = in_array($ip, ['127.0.0.1', '::1'], true);
  $forceTest  = getenv('UPN_RATELIMIT_TEST') === '1';
  if ($isLoopback && !$forceTest) return;

  // Shramba znotraj projekta; direkten dostop prek URL-ja zaprt z .htaccess
  $dir = __DIR__ . '/.ratelimit';
  if (!is_dir($dir)) @mkdir($dir, 0700, true);
  $file = $dir . '/' . sha1($ip) . '.json';

  $now = time();
  $fh = @fopen($file, 'c+');
  if ($fh === false) return; // fail-open: ob napaki shrambe ne blokiramo
  flock($fh, LOCK_EX);

  $raw = stream_get_contents($fh);
  $timestamps = $raw ? (json_decode($raw, true) ?: []) : [];

  // Prune: obdrži samo znotraj okna
  $cutoff = $now - RATE_LIMIT_WINDOW;
  $timestamps = array_values(array_filter($timestamps, fn($t) => $t > $cutoff));

  if (count($timestamps) >= RATE_LIMIT_MAX) {
    $retry = RATE_LIMIT_WINDOW - ($now - min($timestamps));
    flock($fh, LOCK_UN);
    fclose($fh);
    http_response_code(429);
    header('Retry-After: ' . max(1, $retry));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Preveč zahtevkov. Poskusite znova čez minuto.'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Zabeleži trenutni zahtevek
  $timestamps[] = $now;
  ftruncate($fh, 0);
  rewind($fh);
  fwrite($fh, json_encode($timestamps));
  flock($fh, LOCK_UN);
  fclose($fh);
}
