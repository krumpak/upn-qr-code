<?php

function upn_rules(): array {
  return [
    'placilo_znesek' => [
      'label'       => 'Znesek',
      'required'    => true,
      'requiredMsg' => 'Znesek je obvezen.',
      'pattern'     => '^\d+\.\d{2}$',
      'patternMsg'  => 'Znesek ni v pravilni obliki.',
      'numeric'     => true,
      'numStep'     => 0.01,
      'numGt'       => 0,
      'numGtMsg'    => 'Znesek mora biti večji od 0.',
      'numLte'      => 999999999.99,
      'numLteMsg'   => 'Znesek je previsok (največ 999.999.999,99 EUR).',
    ],
    'placilo_datum' => [
      'label'       => 'Datum plačila',
      'required'    => true,
      'requiredMsg' => 'Datum plačila je obvezen.',
      'dateFormat'  => 'd.m.Y',
      'formatMsg'   => 'Datum plačila ni v pravilni obliki.',
      'notPast'     => true,
      'notPastMsg'  => 'Datum plačila ne sme biti v preteklosti.',
    ],
    'placilo_koda_namena' => [
      'label'       => 'Koda namena',
      'required'    => true,
      'requiredMsg' => 'Koda namena je obvezna.',
      'exactlen'    => 4,
      'pattern'     => '^[A-Z]{4}$',
      'patternMsg'  => 'Koda namena mora vsebovati 4 velike črke (A-Z).',
      'uppercase'   => true,
      'iso88592'    => false,
    ],
    'placilo_namen' => [
      'label'       => 'Namen plačila',
      'required'    => true,
      'requiredMsg' => 'Namen plačila je obvezen.',
      'maxlen'      => 42,
      'iso88592'    => true,
    ],
    'placilo_referenca_oznaka' => [
      'label'       => 'Oznaka reference',
      'required'    => true,
      'requiredMsg' => 'Oznaka reference je obvezna.',
      'enum'        => ['SI', 'RF'],
      'enumMsg'     => 'Oznaka reference je lahko le SI ali RF.',
      'uppercase'   => true,
    ],
    'placilo_referenca_model' => [
      'label'       => 'Model reference',
      'required'    => true,
      'requiredMsg' => 'Model reference je obvezen.',
      'exactlen'    => 2,
      'pattern'     => '^\d{2}$',
      'patternMsg'  => 'Model reference morata biti 2 števki.',
      'errorKey'    => 'placilo_referenca_oznaka',
    ],
    'placilo_referenca_sklic' => [
      'label'           => 'Sklic',
      'required'        => true,
      'requiredMsg'     => 'Sklic je obvezen (razen pri modelu 99).',
      'optionalIfModel' => '99',
      'maxlen'          => 22,
      'pattern'         => '^[A-Z0-9\\-]+$',
      'flags'           => 'i',
      'patternMsg'      => 'Referenca lahko vsebuje le črke, številke in vezaje (-).',
      'errorKey'        => 'placilo_referenca_oznaka',
    ],
    'placnik_naziv' => [
      'label'       => 'Naziv plačnika',
      'required'    => true,
      'requiredMsg' => 'Naziv plačnika je obvezen.',
      'maxlen'      => 40,
      'iso88592'    => true,
    ],
    'placnik_naslov' => [
      'label'    => 'Naslov plačnika',
      'required' => false,
      'maxlen'   => 33,
      'iso88592' => true,
    ],
    'placnik_kraj' => [
      'label'    => 'Kraj plačnika',
      'required' => false,
      'maxlen'   => 33,
      'iso88592' => true,
    ],
    'prejemnik_naziv' => [
      'label'       => 'Naziv prejemnika',
      'required'    => true,
      'requiredMsg' => 'Naziv prejemnika je obvezen.',
      'maxlen'      => 33,
      'iso88592'    => true,
    ],
    'prejemnik_naslov' => [
      'label'       => 'Naslov prejemnika',
      'required'    => true,
      'requiredMsg' => 'Naslov prejemnika je obvezen.',
      'maxlen'      => 33,
      'iso88592'    => true,
    ],
    'prejemnik_kraj' => [
      'label'       => 'Kraj prejemnika',
      'required'    => true,
      'requiredMsg' => 'Kraj prejemnika je obvezen.',
      'maxlen'      => 33,
      'iso88592'    => true,
    ],
    'prejemnik_iban' => [
      'label'         => 'IBAN prejemnika',
      'required'      => true,
      'requiredMsg'   => 'IBAN je obvezen.',
      'normalizeIban' => true,
      'pattern'       => '^SI\d{17}$',
      'patternMsg'    => 'IBAN ni pravilne oblike (SI + 17 številk).',
    ],
  ];
}

function upn_normalize_iban(string $s): string {
  return strtoupper(preg_replace('/\s+/', '', $s));
}

function upn_iso88592_ok(string $s): bool {
  // //IGNORE izpusti nepodprte znake; round-trip pokaže, ali je kateri bil izpuščen.
  $to   = @iconv('UTF-8', 'ISO-8859-2//IGNORE', $s);
  if ($to === false) return false;
  $back = @iconv('ISO-8859-2', 'UTF-8', $to);
  return $back === $s;
}

function upn_attrs(string $field): string {
  $rules = upn_rules();
  if (!isset($rules[$field])) return '';
  $r     = $rules[$field];
  $attrs = [];
  if (($r['required'] ?? false) && !isset($r['optionalIfModel'])) $attrs[] = 'required';
  if (isset($r['exactlen']))   $attrs[] = 'minlength="' . $r['exactlen'] . '" maxlength="' . $r['exactlen'] . '"';
  elseif (isset($r['maxlen'])) $attrs[] = 'maxlength="' . $r['maxlen'] . '"';
  // numerične meje (type=number): step iz numStep, min iz ekskluzivnega numGt+step, max iz numLte
  if (isset($r['numStep'])) $attrs[] = 'step="' . sprintf('%.2f', $r['numStep']) . '"';
  if (isset($r['numGt']))   $attrs[] = 'min="'  . sprintf('%.2f', $r['numGt'] + ($r['numStep'] ?? 0)) . '"';
  if (isset($r['numLte']))  $attrs[] = 'max="'  . sprintf('%.2f', $r['numLte']) . '"';
  return implode(' ', $attrs);
}

function upn_validate(array $input): array {
  $rules  = upn_rules();
  $errors = [];
  $add    = static function (string $key, string $msg) use (&$errors): void {
    $errors[$key][] = $msg;
  };

  // trim vseh string vnosov
  foreach ($input as $k => $v) {
    if (is_string($v)) $input[$k] = trim($v);
  }

  foreach ($rules as $field => $r) {
    $val    = $input[$field] ?? '';
    $errKey = $r['errorKey'] ?? $field;

    if ($r['uppercase']     ?? false) $val = strtoupper($val);
    if ($r['normalizeIban'] ?? false) $val = upn_normalize_iban($val);

    // required — sklic je pogojno obvezen glede na model
    $isRequired = $r['required'] ?? false;
    if ($isRequired && isset($r['optionalIfModel'])) {
      $model = strtoupper(trim($input['placilo_referenca_model'] ?? ''));
      if ($model === $r['optionalIfModel']) $isRequired = false;
    }
    if ($isRequired && $val === '') {
      $add($errKey, $r['requiredMsg'] ?? ($r['label'] ?? $field) . ' je obvezen/-na.');
      continue;
    }
    if ($val === '') continue;

    if (isset($r['exactlen']) && mb_strlen($val) !== $r['exactlen'])
      $add($errKey, ($r['label'] ?? $field) . ' mora biti dolg/-a točno ' . $r['exactlen'] . ' znake/-ov.');

    if (isset($r['maxlen']) && mb_strlen($val) > $r['maxlen'])
      $add($errKey, ($r['label'] ?? $field) . ' je predolg/-a (največ ' . $r['maxlen'] . ' znakov).');

    if (isset($r['enum']) && !in_array($val, $r['enum'], true))
      $add($errKey, $r['enumMsg'] ?? ($r['label'] ?? $field) . ' ni veljavna vrednost.');

    $patternOk = true;
    if (isset($r['pattern'])) {
      $flags = $r['flags'] ?? '';
      if (!preg_match('/' . $r['pattern'] . '/' . $flags, $val)) {
        $add($errKey, $r['patternMsg'] ?? ($r['label'] ?? $field) . ' ni v pravilni obliki.');
        $patternOk = false;
      }
    }

    if (($r['numeric'] ?? false) && $patternOk) {
      $num = (float)$val;
      if (isset($r['numGt']) && $num <= $r['numGt'])
        $add($errKey, $r['numGtMsg'] ?? ($r['label'] ?? $field) . ' mora biti večji od ' . $r['numGt'] . '.');
      if (isset($r['numLte']) && $num > $r['numLte'])
        $add($errKey, $r['numLteMsg'] ?? ($r['label'] ?? $field) . ' je previsok.');
    }

    if (isset($r['dateFormat'])) {
      $datum = DateTime::createFromFormat($r['dateFormat'], $val);
      $valid = $datum && $datum->format($r['dateFormat']) === $val;
      if (!$valid) {
        $add($errKey, $r['formatMsg'] ?? ($r['label'] ?? $field) . ' ni v pravilni obliki.');
      } elseif (($r['notPast'] ?? false) && $datum->setTime(0, 0)->getTimestamp() < (new DateTime('today'))->getTimestamp()) {
        $add($errKey, $r['notPastMsg'] ?? ($r['label'] ?? $field) . ' ne sme biti v preteklosti.');
      }
    }

    if (($r['iso88592'] ?? false) && !upn_iso88592_ok($val))
      $add($errKey, ($r['label'] ?? $field) . ' vsebuje znake, ki niso podprti (ISO-8859-2).');
  }

  return $errors;
}
