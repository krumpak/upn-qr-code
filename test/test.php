<?php
define('URL', 'http://localhost:' . (getenv('TEST_PORT') ?: '9099') . '/generate.php');

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
  $data = array_merge([
    'placilo_znesek'           => '10.00',
    'placilo_datum'            => $tomorrow,
    'placilo_koda_namena'      => 'GDSV',
    'placilo_namen'            => 'Test plačilo',
    'placilo_referenca_oznaka' => 'SI',
    'placilo_referenca_model'  => '00',
    'placilo_referenca_sklic'  => '12345',
    'placnik_naziv'            => 'Test Plačnik',
    'placnik_naslov'           => 'Plačnikova ulica 1',
    'placnik_kraj'             => '1000 Ljubljana',
    'prejemnik_naziv'          => 'Test Prejemnik',
    'prejemnik_naslov'         => 'Prejemnikova ulica 2',
    'prejemnik_kraj'           => '2000 Maribor',
    'prejemnik_iban'           => 'SI56 0203 6025 3863 406',
  ], $overrides);

  // referenco sestavimo iz komponent (kot frontend/backend), razen če je eksplicitno podana
  if (!array_key_exists('placilo_referenca', $overrides)) {
    $data['placilo_referenca'] = strtoupper($data['placilo_referenca_oznaka'])
      . $data['placilo_referenca_model']
      . $data['placilo_referenca_sklic'];
  }

  return $data;
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

function raw_lines(array $res): array {
  return explode("\n", $res['body']['raw'] ?? '');
}

function assert_raw_line(array $res, int $idx, string $expected, string $label): void {
  $lines = raw_lines($res);
  $got   = $lines[$idx] ?? '(manjka)';
  $ok    = $got === $expected;
  $mark  = $ok ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m";
  echo "  RAW[$idx]:     $label → pričakovan='$expected'  dobim='$got'  $mark\n";
  if (!$ok)
    throw new RuntimeException("raw vrstica $idx ($label): pričakovan '$expected', dobim '$got'.");
}

function test(string $name, callable $fn): void {
  global $pass, $fail, $testNum, $results;
  $testNum++;
  echo "\n\033[90m" . str_repeat('─', 60) . "\033[0m\n\n";
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
  $res = post_json(valid_input());
  assert_status($res, 200);
  $hasQr = !empty($res['body']['qr']);
  echo "  VSEBINA:    body.qr → " . ($hasQr ? "\033[32m✓ prisoten\033[0m" : "\033[31m✗ manjka\033[0m") . "\n";
  if (!$hasQr)
    throw new RuntimeException("Manjka 'qr' v responsu");
});

test('Več napačnih polj hkrati → vse napake prijavljene', function () {
  $res = post_json(valid_input([
    'placilo_znesek' => 'abc',
    'placilo_datum'  => '2020-01-01',
    'prejemnik_iban' => 'INVALIDIBAN',
  ]));
  assert_status($res, 422);
  assert_has_error($res, 'placilo_znesek');
  assert_has_error($res, 'placilo_datum');
  assert_has_error($res, 'prejemnik_iban');
});

test('Vsa polja napačna → vse napake prijavljene', function () {
  $res = post_json([
    'placilo_znesek'           => 'abc',
    'placilo_datum'            => '2020-01-01',
    'placilo_koda_namena'      => '',
    'placilo_namen'            => '',
    'placilo_referenca_oznaka' => 'SI',
    'placilo_referenca_model'  => '00',
    'placilo_referenca_sklic'  => 'abc!@#',
    'placilo_referenca'        => '',
    'placnik_naziv'            => '',
    'placnik_naslov'           => '',
    'placnik_kraj'             => '',
    'prejemnik_naziv'          => '',
    'prejemnik_naslov'         => '',
    'prejemnik_kraj'           => '',
    'prejemnik_iban'           => 'INVALIDIBAN',
  ]);
  assert_status($res, 422);
  assert_has_error($res, 'placilo_znesek');
  assert_has_error($res, 'placilo_datum');
  assert_has_error($res, 'placilo_koda_namena');
  assert_has_error($res, 'placilo_namen');
  assert_has_error($res, 'placnik_naziv');
  assert_has_error($res, 'prejemnik_naziv');
  assert_has_error($res, 'prejemnik_iban');
});

test('upnZnesek — raw vsebuje 11-mestni znesek', function () {
  $res = post_json(valid_input(['placilo_znesek' => '10.00']));
  assert_status($res, 200);
  $hasZnesek = (bool)preg_match('/^\d{11}$/m', $res['body']['raw'] ?? '');
  echo "  VSEBINA:    raw vsebuje 11-mestni znesek → " . ($hasZnesek ? "\033[32m✓ da\033[0m" : "\033[31m✗ ne\033[0m") . "\n";
  if (!$hasZnesek)
    throw new RuntimeException("raw ne vsebuje 11-mestnega zneska");
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

test('Vsa polja pravilno umeščena v raw payload', function () {
  $in  = valid_input();
  $res = post_json($in);
  assert_status($res, 200);

  assert_raw_line($res, 0,  'UPNQR',                    'identifikator');
  assert_raw_line($res, 5,  $in['placnik_naziv'],       'placnik_naziv');
  assert_raw_line($res, 6,  $in['placnik_naslov'],      'placnik_naslov');
  assert_raw_line($res, 7,  $in['placnik_kraj'],        'placnik_kraj');
  assert_raw_line($res, 8,  '00000001000',              'znesek (10.00 → centi)');
  assert_raw_line($res, 11, $in['placilo_koda_namena'], 'placilo_koda_namena');
  assert_raw_line($res, 12, $in['placilo_namen'],       'placilo_namen');
  assert_raw_line($res, 13, $in['placilo_datum'],       'placilo_datum');
  assert_raw_line($res, 14, 'SI56020360253863406',      'prejemnik_iban (normaliziran)');
  assert_raw_line($res, 15, $in['placilo_referenca'],   'placilo_referenca');
  assert_raw_line($res, 16, $in['prejemnik_naziv'],     'prejemnik_naziv');
  assert_raw_line($res, 17, $in['prejemnik_naslov'],    'prejemnik_naslov');
  assert_raw_line($res, 18, $in['prejemnik_kraj'],      'prejemnik_kraj');
});

test('IBAN s presledki → normaliziran v raw', function () {
  $res = post_json(valid_input());
  assert_status($res, 200);
  assert_raw_line($res, 14, 'SI56020360253863406', 'prejemnik_iban (normaliziran)');
});

test('Kontrolna vsota — zadnja vrstica je 3-mestna', function () {
  $res = post_json(valid_input());
  assert_status($res, 200);
  $lines    = raw_lines($res);
  $checksum = end($lines);
  $ok       = (bool)preg_match('/^\d{3}$/', $checksum);
  echo "  VSEBINA:    kontrolna vsota '$checksum' → " . ($ok ? "\033[32m✓ 3-mestna\033[0m" : "\033[31m✗ napačna\033[0m") . "\n";
  if (!$ok)
    throw new RuntimeException("Kontrolna vsota ni 3-mestna: '$checksum'");
});

test('Znesek 1234.56 → 00000123456 v raw', function () {
  $res = post_json(valid_input(['placilo_znesek' => '1234.56']));
  assert_status($res, 200);
  assert_raw_line($res, 8, '00000123456', 'znesek (1234.56 → centi)');
});

test('Referenca — backend jo sestavi iz komponent (oznaka+model+sklic)', function () {
  $res = post_json(valid_input([
    'placilo_referenca_oznaka' => 'si',        // male črke → normalizacija v velike
    'placilo_referenca_model'  => '00',
    'placilo_referenca_sklic'  => '98765',
    'placilo_referenca'        => 'PONAREJENA', // vrednost s frontenda mora biti ignorirana
  ]));
  assert_status($res, 200);
  assert_raw_line($res, 15, 'SI0098765', 'placilo_referenca (sestavljena na backendu)');
});

// -------------------------------------------------------

$total = $pass + $fail;

// Pregled vseh testov
echo "\n\033[1m" . str_repeat('─', 60) . "\033[0m\n";
echo "\n\033[1;32m PREGLED TESTOV\033[0m\n\n";
echo "\033[1m" . str_repeat('─', 60) . "\033[0m\n\n";
foreach ($results as $r) {
  if ($r['pass']) {
    echo "  \033[32m✓\033[0m  [{$r['num']}] {$r['name']}\n";
  } else {
    echo "  \033[31m✗\033[0m  [{$r['num']}] {$r['name']}\n";
    echo "      \033[31m→ {$r['msg']}\033[0m\n";
  }
}
echo "\n\033[1m" . str_repeat('─', 60) . "\033[0m\n\n";

if ($fail === 0) {
  echo "\033[32m✅ Vsi testi uspešni ($pass/$total)\033[0m\n";
} else {
  echo "\033[31m❌ $fail/$total testov neuspešnih\033[0m\n";
}
echo "\n\033[1m" . str_repeat('─', 60) . "\033[0m\n\n";
exit($fail > 0 ? 1 : 0);
