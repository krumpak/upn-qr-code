<?php
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

/* --- HELPERS --- */
function json_respond(array $data, int $status = 200): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

function upnZnesek($eur) {
  $centi = (int) round(bcmul((string)$eur, '100', 2));
  return str_pad((string)$centi, 11, '0', STR_PAD_LEFT);
}

/* --- INPUT --- */
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  json_respond(['error' => 'Neveljaven JSON.'], 400);
  exit;
}

/* --- VALIDACIJA --- */
$errors = [];

// ZNESEK
if (!($input['placilo_znesek'] ?? '')) {
  $errors['placilo_znesek'][] = 'Znesek je obvezen.';
} else {
  if (!preg_match('/^\d+\.\d{2}$/', $input['placilo_znesek']))
    $errors['placilo_znesek'][] = 'Znesek ni v pravilni obliki.';
  if (preg_match('/^\d+\.\d{2}$/', $input['placilo_znesek']) && (float)$input['placilo_znesek'] <= 0)
    $errors['placilo_znesek'][] = 'Znesek mora biti večji od 0.';
}

// DATUM
if (!($input['placilo_datum'] ?? '')) {
  $errors['placilo_datum'][] = 'Datum plačila je obvezen.';
} else {
  $datum = DateTime::createFromFormat('d.m.Y', $input['placilo_datum']);
  $datumValid = $datum && $datum->format('d.m.Y') === $input['placilo_datum'];
  if (!$datumValid)
    $errors['placilo_datum'][] = 'Datum plačila ni v pravilni obliki.';
  if ($datumValid && $datum->setTime(0, 0)->getTimestamp() < (new DateTime('today'))->getTimestamp())
    $errors['placilo_datum'][] = 'Datum plačila ne sme biti v preteklosti.';
}

// OBVEZNA POLJA
if (!($input['placilo_koda_namena'] ?? '')) $errors['placilo_koda_namena'][] = 'Koda namena je obvezna.';
if (!($input['placilo_namen'] ?? '')) $errors['placilo_namen'][] = 'Namen plačila je obvezen.';
if (!($input['placilo_referenca_oznaka'] ?? '')) $errors['placilo_referenca_oznaka'][] = 'Oznaka reference je obvezna.';
if (!($input['placilo_referenca_model'] ?? '')) $errors['placilo_referenca_oznaka'][] = 'Model reference je obvezen.';
if (!($input['placilo_referenca_sklic'] ?? '')) $errors['placilo_referenca_oznaka'][] = 'Sklic je obvezen.';
if (!($input['placnik_naziv'] ?? '')) $errors['placnik_naziv'][] = 'Naziv plačnika je obvezen.';
if (!($input['prejemnik_naziv'] ?? '')) $errors['prejemnik_naziv'][] = 'Naziv prejemnika je obvezen.';

if (($input['placilo_referenca_sklic'] ?? '') && !preg_match('/^[A-Z0-9\-]+$/i', $input['placilo_referenca_sklic']))
  $errors['placilo_referenca_oznaka'][] = 'Referenca lahko vsebuje le črke, številke in vezaje (-).';

$iban = strtoupper(preg_replace('/\s+/', '', $input['prejemnik_iban'] ?? ''));
if (!$iban) {
  $errors['prejemnik_iban'][] = 'IBAN je obvezen.';
} else {
  if (!preg_match('/^SI\d{17}$/', $iban))
    $errors['prejemnik_iban'][] = 'IBAN ni pravilne oblike (SI + 17 številk).';
}

if (!empty($errors)) {
  json_respond(['errors' => $errors], 422);
  exit;
}

/* --- PAYLOAD --- */
$lines = [
  /*  1. Identifikator       */  "UPNQR",
  /*  2. Verzija             */  "",
  /*  3. Koda namena         */  "",
  /*  4. Referenca           */  "",
  /*  5. Dodatni podatki     */  "",
  /*  6. Plačnik – naziv     */  $input['placnik_naziv'] ?? "",
  /*  7. Plačnik – naslov    */  $input['placnik_naslov'] ?? "",
  /*  8. Plačnik – kraj      */  $input['placnik_kraj'] ?? "",
  /*  9. Znesek              */  upnZnesek((float)$input['placilo_znesek']),
  /* 10. Datum izvršitve     */  "",
  /* 11. Rezervirano         */  "",
  /* 12. Koda namena         */  $input['placilo_koda_namena'],
  /* 13. Namen plačila       */  $input['placilo_namen'] ?? "",
  /* 14. Rok plačila         */  $input['placilo_datum'],
  /* 15. IBAN prejemnika     */  $iban,
  /* 16. Referenca plačila   */  $input['placilo_referenca'] ?? "",
  /* 17. Prejemnik – naziv   */  $input['prejemnik_naziv'],
  /* 18. Prejemnik – naslov  */  $input['prejemnik_naslov'],
  /* 19. Prejemnik – kraj    */  $input['prejemnik_kraj'],
];

$payload = implode("\n", $lines) . "\n";
$checksum = mb_strlen($payload, 'ISO-8859-2');
$payload .= sprintf('%03d', $checksum);

/* --- QR --- */
$result = (new Builder(
  writer: new PngWriter(),
  data: $payload,
  encoding: new Encoding('ISO-8859-2'),
  errorCorrectionLevel: ErrorCorrectionLevel::Medium,
  size: 350,
  margin: 0
))->build();

json_respond([
  'qr'  => $result->getDataUri(),
  'raw' => $payload
]);
