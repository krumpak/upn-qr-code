<?php require __DIR__ . '/validation.php'; ?>
<!DOCTYPE html>
<html lang="sl">
<head>
  <meta charset="UTF-8">
  <base href="<?= rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/') . '/' ?>">
  <title>UPN QR koda</title>
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  
  <!-- Dodatno preprečevanje cachiranja -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <meta name="robots" content="noindex, nofollow">

  <link rel="stylesheet" href="./assets/styles.css">
  <script>
    window.UPN_RULES = <?= json_encode(upn_rules(), JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="./assets/script.js" async defer></script>

</head>
<body>
  <?php
    // header("Location: https://www.primaprodukcija.si/");
    // exit();
  ?>

<h1>UPN QR koda</h1>

<div class="container">
  <div class="form">
    <div class="presets">
      <select id="preset-select">
        <option value="" selected disabled>— izberi shranjen vnos —</option>
      </select>
<button type="button" id="preset-save" title="Shrani trenutno…">💾</button>
      <button type="button" id="preset-delete" title="Izbriši">🗑️</button>
      <button type="button" id="form-clear" title="Počisti obrazec">✕</button>
    </div>

    <div id="errors" class="error"></div>
    
    <form id="qrForm">

      <div class="row">
        <label>Znesek (€)</label>
        <input
          type="number"
          name="placilo_znesek"
          placeholder="00.01"
          <?= upn_attrs('placilo_znesek') ?>
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Koda namena</label>
        <input
          name="placilo_koda_namena"
          list="list_kode"
          placeholder="OTHR"
          <?= upn_attrs('placilo_koda_namena') ?>
        >
        <!-- https://www.racunovodja.com/clanki.asp?clanek=5242 -->
        <datalist id="list_kode">
          <option value="OTHR">OTHR - Razno</option>
          <option value="IVPT">IVPT - Plačilo računa</option>
          <option value="ADVA">ADVA - Plačilo vnaprej / predplačilo</option>
          <option value="GDSV">GDSV - Kupoprodaja blaga in storitev</option>
          <option value="RENT">RENT - Najemnina</option>
          <option value="TAXS">TAXS - Plačilo davkov</option>
          <option value="GOVT">GOVT - Plačilo v dobro / breme državnega organa</option>
          <option value="COST">COST - Stroški</option>
          <option value="INSU">INSU - Državno zavarovanje</option>
          <option value="LIFI">LIFI - Življenjsko zavarovanje</option>
          <option value="ENRG">ENRG - Plačilo energetike</option>
          <option value="ELEC">ELEC - Plačilo elektrike</option>
        </datalist>
        
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Namen plačila</label>
        <input
          name="placilo_namen"
          placeholder="Namen plačila"
          <?= upn_attrs('placilo_namen') ?>
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Referenca</label>
        <input class="referenca" readonly>

        <input
          name="placilo_referenca_oznaka"
          list="list_referenca_oznaka"
          placeholder="SI"
          <?= upn_attrs('placilo_referenca_oznaka') ?>
        >
        <datalist id="list_referenca_oznaka">
          <option value="SI">SI - Slovensko SEPA območje</option>
          <option value="RF">RF - Celotno SEPA območje (tudi izven Slovenije)</option>
        </datalist>

        <input
          name="placilo_referenca_model"
          list="list_referenca_model"
          placeholder="00"
          <?= upn_attrs('placilo_referenca_model') ?>
        >
        <!-- https://www.sportna-oblacila.si/kaj-je-sklic/ -->
        <datalist id="list_referenca_model">
          <option value="00">00 - Dodajamo sklic</option>
          <option value="07">07 - Distributerji električne energije</option>
          <option value="12">12 - Večino distributerjev</option>
          <option value="19">19 - FURS obveznosti</option>
          <option value="99">99 - Sklic je izpuščen/prazen</option>
        </datalist>

        <input
          name="placilo_referenca_sklic"
          placeholder="0000-00"
          <?= upn_attrs('placilo_referenca_sklic') ?>
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Datum plačila</label>
        <input
          type="date"
          name="placilo_datum"
          value="<?= date('Y-m-d') ?>"
          <?= upn_attrs('placilo_datum') ?>
        >
        <span class="error"></span>
      </div>

      <hr>

      <h2>Plačnik</h2>

      <div class="row">
        <label>Naziv</label>
        <input
          name="placnik_naziv"
          placeholder="Ime in priimek"
          <?= upn_attrs('placnik_naziv') ?>
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Naslov</label>
        <input
          name="placnik_naslov"
          placeholder="Slovenska cesta 123"
          <?= upn_attrs('placnik_naslov') ?>
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Kraj</label>
        <input
          name="placnik_kraj"
          placeholder="1000 Ljubljana"
          <?= upn_attrs('placnik_kraj') ?>
        >
        <span class="error"></span>
      </div>

      <hr>

      <h2>Prejemnik</h2>

      <div class="row">
        <label>IBAN</label>
        <input
          name="prejemnik_iban"
          placeholder="SI56 0000 0000 0000 000"
          <?= upn_attrs('prejemnik_iban') ?>
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Naziv</label>
        <input
          name="prejemnik_naziv"
          placeholder="Ime in priimek ali podjetje"
          <?= upn_attrs('prejemnik_naziv') ?>
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Naslov</label>
        <input
          name="prejemnik_naslov"
          placeholder="Slovenska cesta 123"
          <?= upn_attrs('prejemnik_naslov') ?>
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Kraj</label>
        <input
          name="prejemnik_kraj"
          placeholder="1000 Ljubljana"
          <?= upn_attrs('prejemnik_kraj') ?>
        >
        <span class="error"></span>
      </div>

    </form>
  </div>
  <div class="result">
    <button class="btn-block" id="placeholder-button">Generiraj QR</button>
    <a id="placeholder-link" target="_blank" rel="noopener noreferrer">
      <span id="placeholder-text">&nbsp;</span>
      <div class="placeholder-wrapper">
        <img id="placeholder-img" data-status="initial" src="./assets/qr-code.svg" alt="UPN QR" />
        <span class="spinner"></span>
      </div>
    </a>

    <pre id="placeholder-result">n/a</pre>
  </div>
</div>

</body>
</html>
