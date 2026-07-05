<?php
define('URL', 'http://localhost:9003/generate.php');

$pass = 0;
$fail = 0;
$testNum = 0;
$results = [];

function post(string $body): array {
  if (!strlen($body)) {
    echo "  POŠLJEM:    (prazen body)\n";
  } else {
    $decoded = json_decode($body, true);
    if ($decoded !== null) {
      $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      $indented = preg_replace('/^/m', '              ', $pretty);
      echo "  POŠLJEM:    " . ltrim($indented) . "\n";
    } else {
      echo "  POŠLJEM:    $body\n";
    }
  }
  $ctx = stream_context_create(['http' => [
    'method'        => 'POST',
    'header'        => 'Content-Type: application/json',
    'content'       => $body,
    'ignore_errors' => true,
  ]]);
  $raw = @file_get_contents(URL, false, $ctx);
  $headers = http_get_last_response_headers() ?? [];
  preg_match('/HTTP\/[\d.]+ (\d+)/', $headers[0] ?? '', $m);
  return [
    'status' => (int)($m[1] ?? 0),
    'body'   => json_decode($raw ?: '{}', true) ?? [],
  ];
}

function post_json(array $data): array {
  return post(json_encode($data, JSON_UNESCAPED_UNICODE));
}

function valid_input(array $overrides = []): array {
  $tomorrow = (new DateTime('tomorrow'))->format('d.m.Y');
  return array_merge([
    'placilo_znesek'           => '10.00',
    'placilo_datum'            => $tomorrow,
    'placilo_koda_namena'      => 'GDSV',
    'placilo_namen'            => 'Test plačilo',
    'placilo_referenca_oznaka' => 'SI',
    'placilo_referenca_model'  => '00',
    'placilo_referenca_sklic'  => '12345',
    'placilo_referenca'        => 'SI0012345',
    'placnik_naziv'            => 'Test Plačnik',
    'placnik_naslov'           => '',
    'placnik_kraj'             => '',
    'prejemnik_naziv'          => 'Test Prejemnik',
    'prejemnik_naslov'         => '',
    'prejemnik_kraj'           => '',
    'prejemnik_iban'           => 'SI56020360253863406',
  ], $overrides);
}

function assert_status(array $res, int $expected): void {
  $ok   = $res['status'] === $expected;
  $mark = $ok ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m";
  echo "  STATUS:     pričakovan=$expected  dobim={$res['status']}  $mark\n";
  if (!$ok)
    throw new RuntimeException("HTTP {$res['status']}, pričakovan $expected. Body: " . json_encode($res['body']));
}

function assert_has_error(array $res, string $field): void {
  $has  = !empty($res['body']['errors'][$field]);
  $mark = $has ? "\033[32m✓ prisoten\033[0m" : "\033[31m✗ manjka\033[0m";
  echo "  NAPAKA:     errors.$field → $mark\n";
  if (!$has)
    throw new RuntimeException("Manjka napaka za '$field'. Body: " . json_encode($res['body']));
}

function test(string $name, callable $fn): void {
  global $pass, $fail, $testNum, $results;
  $testNum++;
  echo "\033[90m" . str_repeat('─', 60) . "\033[0m\n";
  echo "\033[1;33m[$testNum] $name\033[0m\n";
  try {
    $fn();
    echo "  \033[32m✓ PASS\033[0m\n";
    $pass++;
    $results[] = ['num' => $testNum, 'name' => $name, 'pass' => true, 'msg' => null];
  } catch (Throwable $e) {
    echo "  \033[31m✗ FAIL\033[0m → {$e->getMessage()}\n";
    $fail++;
    $results[] = ['num' => $testNum, 'name' => $name, 'pass' => false, 'msg' => $e->getMessage()];
  }
}

// -------------------------------------------------------

test('Prazen body → 400', function () {
  $res = post('');
  assert_status($res, 400);
  $hasErr = !empty($res['body']['error']);
  echo "  VSEBINA:    body.error → " . ($hasErr ? "\033[32m✓ prisoten\033[0m" : "\033[31m✗ manjka\033[0m") . "\n";
  if (!$hasErr)
    throw new RuntimeException("Manjka 'error' ključ");
});

test('Neveljaven JSON → 400', function () {
  $res = post('{invalid}');
  assert_status($res, 400);
});

test('Znesek manjka → napaka', function () {
  $res = post_json(valid_input(['placilo_znesek' => '']));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_znesek');
});

test('Znesek = 0.00 → napaka', function () {
  $res = post_json(valid_input(['placilo_znesek' => '0.00']));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_znesek');
});

test('Znesek napačna oblika (abc) → napaka', function () {
  $res = post_json(valid_input(['placilo_znesek' => 'abc']));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_znesek');
});

test('Datum manjka → napaka', function () {
  $res = post_json(valid_input(['placilo_datum' => '']));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_datum');
});

test('Datum napačna oblika (yyyy-mm-dd) → napaka', function () {
  $res = post_json(valid_input(['placilo_datum' => '2030-01-01']));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_datum');
});

test('Datum v preteklosti → napaka', function () {
  $res = post_json(valid_input(['placilo_datum' => '01.01.2020']));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_datum');
});

test('Manjkajoča obvezna polja → napake', function () {
  $res = post_json(valid_input([
    'placilo_koda_namena' => '',
    'placilo_namen'       => '',
    'placnik_naziv'       => '',
    'prejemnik_naziv'     => '',
  ]));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_koda_namena');
  assert_has_error($res, 'placilo_namen');
  assert_has_error($res, 'placnik_naziv');
  assert_has_error($res, 'prejemnik_naziv');
});

test('Referenca z neveljavnimi znaki → napaka', function () {
  $res = post_json(valid_input(['placilo_referenca_sklic' => 'abc!@#']));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_referenca_oznaka');
});

test('IBAN manjka → napaka', function () {
  $res = post_json(valid_input(['prejemnik_iban' => '']));
  assert_status($res, 422);
  assert_has_error($res, 'prejemnik_iban');
});

test('IBAN napačna oblika (DE IBAN) → napaka', function () {
  $res = post_json(valid_input(['prejemnik_iban' => 'DE89370400440532013000']));
  assert_status($res, 422);
  assert_has_error($res, 'prejemnik_iban');
});

test('IBAN s presledki → normalizacija, QR generiran', function () {
  $res = post_json(valid_input(['prejemnik_iban' => 'SI56 0203 6025 3863 406']));
  assert_status($res, 200);
  $hasQr = !empty($res['body']['qr']);
  echo "  VSEBINA:    body.qr → " . ($hasQr ? "\033[32m✓ prisoten\033[0m" : "\033[31m✗ manjka\033[0m") . "\n";
  if (!$hasQr)
    throw new RuntimeException("Manjka 'qr' v responsu");
});

test('Polni veljavni vnos → QR in raw', function () {
  $res = post_json(valid_input());
  assert_status($res, 200);
  $hasQr  = !empty($res['body']['qr']);
  $hasRaw = !empty($res['body']['raw']);
  echo "  VSEBINA:    body.qr → " . ($hasQr ? "\033[32m✓ prisoten\033[0m" : "\033[31m✗ manjka\033[0m") . "\n";
  echo "  VSEBINA:    body.raw → " . ($hasRaw ? "\033[32m✓ prisoten\033[0m" : "\033[31m✗ manjka\033[0m") . "\n";
  if (!$hasQr)
    throw new RuntimeException("Manjka 'qr'");
  if (!$hasRaw)
    throw new RuntimeException("Manjka 'raw'");
});

test('upnZnesek — raw vsebuje 11-mestni znesek', function () {
  $res = post_json(valid_input(['placilo_znesek' => '10.00']));
  assert_status($res, 200);
  $hasZnesek = (bool)preg_match('/^\d{11}$/m', $res['body']['raw'] ?? '');
  echo "  VSEBINA:    raw vsebuje 11-mestni znesek → " . ($hasZnesek ? "\033[32m✓ da\033[0m" : "\033[31m✗ ne\033[0m") . "\n";
  if (!$hasZnesek)
    throw new RuntimeException("raw ne vsebuje 11-mestnega zneska");
});

// -------------------------------------------------------

$total = $pass + $fail;

// Pregled vseh testov
echo "\n\033[1m" . str_repeat('─', 60) . "\033[0m\n";
echo "\033[1m PREGLED TESTOV\033[0m\n";
echo "\033[1m" . str_repeat('─', 60) . "\033[0m\n";
foreach ($results as $r) {
  if ($r['pass']) {
    echo "  \033[32m✓\033[0m  [{$r['num']}] {$r['name']}\n";
  } else {
    echo "  \033[31m✗\033[0m  [{$r['num']}] {$r['name']}\n";
    echo "      \033[31m→ {$r['msg']}\033[0m\n";
  }
}
echo "\033[1m" . str_repeat('─', 60) . "\033[0m\n";

if ($fail === 0) {
  echo "\033[32m✅ Vsi testi uspešni ($pass/$total)\033[0m\n";
} else {
  echo "\033[31m❌ $fail/$total testov neuspešnih\033[0m\n";
}
exit($fail > 0 ? 1 : 0);
