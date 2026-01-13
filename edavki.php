<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  define('AUTH_TOKEN', '$1RSoqA^ZTGmt^qK9ulG0r#X3eSPMn$EOilLh^R@zI6zfrFHQV');

  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *'); 
  header('Access-Control-Allow-Methods: POST');
  header('Access-Control-Allow-Headers: Content-Type, Authorization');

  // get bearer token from header
  $token = $_SERVER['HTTP_AUTHORIZATION'];
  if (!$token) {
    http_response_code(403);
    echo json_encode([
      'code' => 403,
      'success' => false,
      'message' => 'Žeton ne obstaja.',
    ]);
    exit;
  }


  // parse bearer token 
  $tokenBearer = str_replace('Bearer ', '', $token);

  if ($tokenBearer !== AUTH_TOKEN) {
    http_response_code(403);
    echo json_encode([
      'code' => 403,
      'success' => false,
      'message' => 'Avtentikacija ni uspešna.',
    ]);
    exit;
  }

  $input = json_decode(file_get_contents('php://input'), true);
  if (!$input) {
    http_response_code(401);
    echo json_encode([
      'code' => 401,
      'success' => false,
      'message' => 'JSON ne obstaja.',
    ]);
    exit;
  }

  if ($tokenBearer === AUTH_TOKEN && $input['action'] === 'auth') {
    http_response_code(200);
    echo json_encode([
      'code' => 200,
      'success' => true,
      'message' => 'Avtentikacija je uspešna.',
    ]);
    exit;
  }

  if ($tokenBearer === AUTH_TOKEN && $input['action'] === 'generate') {
    $rok = new DateTime();
    $rok->setDate(
        (int)$rok->format('Y'), // današnje leto
        (int)$rok->format('m'), // današnji mesec
        20                        // dan = 20
    );

    $danVTednu = $rok->format('N'); // 1 = ponedeljek, 6 = sobota, 7 = nedelja

    if ($danVTednu == 7) {
        // nedelja → dodaj 1 dan (ponedeljek)
        $rok->modify('+1 day');
    } elseif ($danVTednu == 6) {
        // sobota → odštej 1 dan (petek)
        $rok->modify('-1 day');
    }
    
    $za = new DateTime(date($rok->format('Y-m-d')));
    $za->modify('-1 month');

    $rokPlacila = $rok->format('d.m.Y');
    $zaMesec = $za->format('m/Y');
    $img_name = $za->format('m-Y');
    $koda = 'TAXS';

    $placnik = [
      'naziv' => 'PRIMA PRODUKCIJA, GORAZD KRUMPAK S.P.',
      'naslov' => 'lITOSTROJSKA CESTA 025',
      'kraj' => '1000 LJUBLJANA',
      'drzava' => 'SLOVENIJA',
    ];
    
    $data = [
      'akontacija' => [
        'rok' => $rokPlacila,
        'namen' => "Akontacija dohodnine $zaMesec",
        'znesek' => '281.27',
        'iban' => 'SI56011008881000030',
        'referenca' => 'SI1973013242-40002',
        'koda' => $koda,
        'placnik' => $placnik,
        'prejemnik' => [
          'naziv' => 'PDP - PRORAČUN DRŽAVE',
          'naslov' => 'GREGORČIČEVA ULICA 020',
          'kraj' => '1000 LJUBLJANA',
          'drzava' => 'SLOVENIJA',
        ],
        'img_name' => $img_name,
      ],
      'PIZ' => [
        'rok' => $rokPlacila,
        'namen' => "Prispevki za PIZ $zaMesec",
        'znesek' => '349.90',
        'iban' => 'SI56011008882000003',
        'referenca' => 'SI1973013242-44008',
        'koda' => $koda,
        'placnik' => $placnik,
        'prejemnik' => [
          'naziv' => 'PDP - ZPIZ',
          'naslov' => 'KOLODVORSKA ULICA 015',
          'kraj' => '1000 LJUBLJANA',
          'drzava' => 'SLOVENIJA',
        ],
        'img_name' => $img_name,
      ],
      'ZZ' => [
        'rok' => $rokPlacila,
        'namen' => "Prispevki za ZZ $zaMesec",
        'znesek' => '259.18',
        'iban' => 'SI56011008883000073',
        'referenca' => 'SI1973013242-45004',
        'koda' => $koda,
        'placnik' => $placnik,
        'prejemnik' => [
          'naziv' => 'PDP - ZZZS',
          'naslov' => 'MIKLOŠIČEVA CESTA 024',
          'kraj' => '1000 LJUBLJANA',
          'drzava' => 'SLOVENIJA',
        ],
        'img_name' => $img_name,
      ],
      'STV' => [
        'rok' => $rokPlacila,
        'namen' => "Prispevki za STV $zaMesec",
        'znesek' => '2.88',
        'iban' => 'SI56011008881000030',
        'referenca' => 'SI1973013242-43001',
        'koda' => $koda,
        'placnik' => $placnik,
        'prejemnik' => [
          'naziv' => 'PDP - PRORAČUN DRŽAVE',
          'naslov' => 'GREGORČIČEVA ULICA 020',
          'kraj' => '1000 LJUBLJANA',
          'drzava' => 'SLOVENIJA',
        ],
        'img_name' => $img_name,
      ],
      'ZAP' => [
        'rok' => $rokPlacila,
        'namen' => "Prispevki za ZAP $zaMesec",
        'znesek' => '2.87',
        'iban' => 'SI56011008881000030',
        'referenca' => 'SI1973013242-42005',
        'koda' => $koda,
        'placnik' => $placnik,
        'prejemnik' => [
          'naziv' => 'PDP - PRORAČUN DRŽAVE',
          'naslov' => 'GREGORČIČEVA ULICA 020',
          'kraj' => '1000 LJUBLJANA',
          'drzava' => 'SLOVENIJA',
        ],
        'img_name' => $img_name,
      ],
    ];

    http_response_code(200);
    echo json_encode([
      'code' => 200,
      'success' => true,
      'message' => 'ok',
      'data' => $data,
    ]);
    exit;
  }

  http_response_code(400);
  echo json_encode([
    'code' => 400,
    'success' => false,
    'message' => 'Neznana napaka.',
  ]);
  exit;
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
  <meta charset="UTF-8">
  <title>eDavki</title>
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  
  <!-- Dodatno preprečevanje cachiranja -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <style>
    .hidden {
      display: none;
    }
  </style>
</head>
<body>
  <div id="content" class="hidden">
    <button id="generate">Generiraj</button>
    <div id="result"></div>
    <hr>
    <button id="logout">Odjava</button>
  </div>
  <div id="login-form" class="hidden">
    <input type="password" name="token" id="token" placeholder="Vnesi žeton" required>
    <button id="login">Prijava</button>
  </div>
  <script src="./assets/edavki.js" async defer></script>
</body>
</html>