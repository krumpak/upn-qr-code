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
        'znesek' => '188.03',
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
        'znesek' => '370.51',
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
      'ZZ in DO' => [
        'rok' => $rokPlacila,
        'namen' => "Prispevki za ZZ in DO $zaMesec",
        'znesek' => '272.26',
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
        'znesek' => '3.04',
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
        'znesek' => '3.04',
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
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Lato', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    #result {
      /* grid postavitev kartic */
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px;

      /* malo zraka okrog */
      padding: 12px;
      margin-top: 12px;
    }

    /* CARD */
    #result .card {
      background: #fff;
      border: 1px solid rgba(0,0,0,.08);
      border-radius: 14px;
      padding: 14px 14px 12px;
      box-shadow: 0 6px 18px rgba(0,0,0,.08);
      transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;

      /* da daljša vsebina lepo dela */
      min-height: 120px;
      overflow: hidden;
    }

    /* Hover */
    #result .card:hover{
      transform: translateY(-2px);
      box-shadow: 0 10px 26px rgba(0,0,0,.12);
      border-color: rgba(0,0,0,.14);
    }

    #result .card h2 {
      font-weight: 700;
      letter-spacing: 0.2px;
      margin: .5em 0;
    }

    #result .card a {
      color: inherit;
      text-decoration: none;
      display: inline-block;
    }

    #result .card img {
      object-fit: contain;
      width: 100%;
      height: 100%;
      object-position: center;
    }

    button {
      appearance: none;
      border: none;
      background: #2563eb; /* modra */
      color: #fff;
      font-family: 'Lato', system-ui, sans-serif;
      font-size: 0.95rem;
      font-weight: 700;
      padding: 10px 16px;
      border-radius: 10px;
      cursor: pointer;
      transition: background .15s ease, transform .1s ease, box-shadow .15s ease;
      box-shadow: 0 4px 12px rgba(37,99,235,.25);
    }

    button:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(37,99,235,.35);
    }

    #logout {
      position: fixed;
      right: 1em;
      top: 1em;
    }

    input,
    select,
    textarea{
      width: 33%;
      font-family: 'Lato', system-ui, sans-serif;
      font-size: 0.95rem;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid rgba(0,0,0,.15);
      background: #fff;
      color: #111827;
      outline: none;

      transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }

    /* Placeholder */
    input::placeholder,
    textarea::placeholder{
      color: rgba(0,0,0,.45);
    }

    /* Focus */
    input:focus,
    select:focus,
    textarea:focus{
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37,99,235,.15);
    }

    /* Hover */
    input:hover,
    select:hover,
    textarea:hover{
      border-color: rgba(0,0,0,.25);
    }

    .hidden {
      display: none;
    }
  </style>
</head>
<body>
  <div id="content" class="hidden">
    <button id="generate">Generiraj</button>
    <div id="result"></div>
    <br>
    <button id="logout">Odjava</button>
  </div>
  <div id="login-form" class="hidden">
    <input type="password" name="token" id="token" placeholder="Vnesi žeton" required>
    <button id="login">Prijava</button>
  </div>
  <script src="./assets/edavki.js" async defer></script>
</body>
</html>