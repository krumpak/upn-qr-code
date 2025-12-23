<?php
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  http_response_code(400);
  echo json_encode(['error' => 'Neveljaven JSON.']);
  exit;
}

/* --- VALIDACIJA --- */
if (!preg_match('/^\d+\.\d{2}$/', $input['placilo_znesek'] ?? '')) {
  echo json_encode(['error' => 'Neveljaven znesek.', 'id' => 'placilo_znesek']);
  exit;
}

if (!empty($input['placilo_referenca']) &&
    !preg_match('/^[A-Z0-9\-]+$/i', $input['placilo_referenca'])) {
  echo json_encode(['error' => 'Neveljavna referenca.', 'id' => 'placilo_referenca']);
  exit;
}

if (!preg_match('/^\d{8}$/', $input['placilo_datum'] ?? '')) {
  echo json_encode(['error' => 'Neveljaven datum.', 'id' => 'placilo_datum']);
  exit;
}

if (!preg_match('/^SI\d{17}$/', $input['prejemnik_iban'] ?? '')) {
  echo json_encode(['error' => 'Neveljaven IBAN.', 'id' => 'prejemnik_iban']);
  exit;
}

/* --- HELPERS --- */
function upnZnesek($eur) {
  return str_pad((string) round($eur * 100), 11, '0', STR_PAD_LEFT);
}

/* --- PAYLOAD --- */
$lines = [
  "UPNQR",
  "",
  "",
  "",
  "",
  $input['placnik_naziv'] ?? "",
  $input['placnik_naslov'] ?? "",
  $input['placnik_kraj'] ?? "",
  upnZnesek((float)$input['placilo_znesek']),
  $input['placilo_datum'],
  "",
  $input['placilo_namena_koda'],
  $input['placilo_namen'] ?? "",
  $input['placilo_datum'],
  $input['prejemnik_iban'],
  $input['placilo_referenca'] ?? "",
  $input['prejemnik_naziv'],
  $input['prejemnik_naslov'],
  $input['prejemnik_kraj'],
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

echo json_encode([
  'qr'  => $result->getDataUri(),
  'raw' => $payload
], JSON_UNESCAPED_UNICODE);
