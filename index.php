<!DOCTYPE html>
<html lang="sl">
<head>
  <meta charset="UTF-8">
  <title>UPN QR koda</title>
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  
  <!-- Dodatno preprečevanje cachiranja -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  
  <link rel="stylesheet" href="./assets/styles.css">
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
    <form id="qrForm">

      <div class="row">
        <label>Znesek (€)</label>
        <input
          type="number"
          name="placilo_znesek"
          value="70.00"
          step="0.01"
          min="0.01"
          required
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Koda namena</label>
        <input
          name="placilo_namena_koda"
          minlength="4"
          maxlength="4"
          list="kode"
          placeholder="ADVA"
          value="GDSV"
          required
        >
        <datalist id="kode">
          <option value="OTHR"></option>
          <option value="IVPT"></option>
          <option value="ADVA"></option>
          <option value="GDSV"></option>
          <option value="RENT"></option>
          <option value="TAXS"></option>
          <option value="GOVT"></option>
          <option value="COST"></option>
          <option value="INSU"></option>
          <option value="LIFI"></option>
        </datalist>
      </div>

      <div class="row">
        <label>Namen plačila</label>
        <input
          name="placilo_namen"
          placeholder="Namen plačila"
          value="Plačilo po ponudbi 2024-01/P"
          maxlength="140"
          required
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Referenca</label>
        <input
          name="placilo_referenca"
          placeholder="SI00-"
          value="SI00-2024-01"
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Datum plačila</label>
        <input
          type="date"
          name="placilo_datum"
          value="<?= date('Y-m-d') ?>"
          required
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
          value="Gorazd Krumpak"
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Naslov</label>
        <input
          name="placnik_naslov"
          placeholder="Slovenska cesta 123"
          value="Litostrojska cesta 25"
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Kraj</label>
        <input
          name="placnik_kraj"
          placeholder="1000 Ljubljana"
          value="1000 Ljubljana"
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
          value="SI56 0000 0000 0000 000"
          required
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Naziv</label>
        <input
          name="prejemnik_naziv"
          placeholder="Ime in priimek ali podjetje"
          required
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Naslov</label>
        <input
          name="prejemnik_naslov"
          placeholder="Slovenska cesta 123"
          required
        >
        <span class="error"></span>
      </div>

      <div class="row">
        <label>Kraj</label>
        <input
          name="prejemnik_kraj"
          placeholder="1000 Ljubljana"
          required
        >
        <span class="error"></span>
      </div>

    </form>

    <div id="errors" class="error"></div>
  </div>
  <div class="result">
    <button id="placeholder-button">Generiraj QR</button>
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
