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

/* --- RATE LIMIT --- */
require __DIR__ . '/ratelimit.php';
rate_limit_check();

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
require __DIR__ . '/validation.php';
$errors = upn_validate($input);
if (!empty($errors)) {
  json_respond(['errors' => $errors], 422);
  exit;
}

/* --- REFERENCA --- */
$iban     = upn_normalize_iban($input['prejemnik_iban'] ?? '');
$referenca = strtoupper(trim($input['placilo_referenca_oznaka']))
  . trim($input['placilo_referenca_model'])
  . trim($input['placilo_referenca_sklic']);

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
  /* 16. Referenca plačila   */  $referenca,
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
