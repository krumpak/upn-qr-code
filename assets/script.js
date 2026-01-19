function $(selector) {
  if (selector.startsWith('.')) {
    const nodes = document.querySelectorAll(selector);
    
    if (nodes.length === 1) {
      return nodes[0];
    } else {
      return nodes;
    }
  } else if (selector.startsWith('#')) {
    return document.getElementById(selector.slice(1));
  } else {
    return document.querySelector(selector);
  }

  return null;
}

prefill();
function prefill () {
  const data = {
    placilo_znesek: '70.00',
    placilo_koda_namena: 'OTHR',
    placilo_namen: 'Plačilo po ponudbi 2024-01/P',
    placilo_referenca_oznaka: 'SI',
    placilo_referenca_model: '00',
    placilo_referenca_sklic: '2024-01',
    placilo_datum: new Date().toISOString().split('T')[0],
    placnik_naziv: 'Gorazd Krumpak',
    placnik_naslov: 'Litostrojska cesta 25',
    placnik_kraj: '1000 Ljubljana',
    prejemnik_iban: 'SI56 0000 0000 0000 001',
    prejemnik_naziv: 'Lea Nemec',
    prejemnik_naslov: 'Litostrojska cesta 25',
    prejemnik_kraj: '1000 Ljubljana',
  }

  Object.keys(data).forEach(key => {
    val = data[key];
    sel = `[name=${key}]`;
    $(sel).value = val;
  })
}

$('#placeholder-button').addEventListener('click', generate);
$('#placeholder-img').addEventListener('click', generate);

async function generate (event) {
  if (event.target && event.target.dataset.status === 'generated') {
    event.preventDefault();
    $('#placeholder-link').click();
    return;
  }
  $('#placeholder-link').classList.add('loading');

  $('#placeholder-link').removeAttribute('href')
  $('#placeholder-link').removeAttribute('download');
  
  $('#placeholder-text').innerHTML = '&nbsp;';
  $('#placeholder-img').src = './assets/qr-code.svg';
  $('#placeholder-img').dataset.status = 'initial'
  $('#placeholder-result').textContent = 'n/a';

  const form = $('#qrForm');
  
  $('.error').forEach(err => err.textContent = '');

  const data = Object.fromEntries(new FormData(form));

  // ZNESEK → vedno "xx.yy"
  let znesek = String(data.placilo_znesek).replace(',', '.');
  if (!/^\d+(\.\d{1,2})?$/.test(znesek)) {
    $('[name=placilo_znesek] + .error').textContent = 'Znesek ni v pravilni obliki.';
  }
  data.placilo_znesek = Number(znesek).toFixed(2);

  // DATUM → YYYYMMDD
  if (!data.placilo_datum) {
    $('[name=placilo_datum] + .error').textContent = 'Datum plačila je obvezen.';
  }
  data.placilo_datum = data.placilo_datum.split('-').reverse().join('.');

  // REFERENCA → dovoljen -
  if (data.placilo_referenca_sklic && !/^[A-Z0-9\-]+$/i.test(data.placilo_referenca_sklic)) {
    $('[name=placilo_referenca_sklic] + .error').textContent = 'Referenca lahko vsebuje le črke, številke in največ do dva vezaja (-).';
  }

  // IBAN → brez presledkov
  data.prejemnik_iban = data.prejemnik_iban.replace(/\s+/g, '').toUpperCase();
  if (!/^SI\d{17}$/.test(data.prejemnik_iban)) {
    $('[name=prejemnik_iban] + .error').textContent = 'IBAN prejemnika ni pravilne oblike.';
  }

  if (Array.from($('.error')).some(errorBox => !!errorBox.textContent)) {
    $('#errors').textContent = 'Popravi vnešene napake';
    $('#placeholder-link').classList.remove('loading');
    return;
  }

  data.placilo_referenca = data.placilo_referenca_oznaka.toUpperCase() + data.placilo_referenca_model + data.placilo_referenca_sklic;

  /* ===============================
     POŠLJI JSON
     =============================== */
  const res = await fetch('generate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });

  const json = await res.json();
  
  await new Promise(resolve => setTimeout(resolve, 1000 * 1));

  if (json.error) {
    $('#errors').textContent = 'Popravi vnešene napake';
    $('#placeholder-link').classList.remove('loading');

    console.error(json.error);
    return;
  }

  $('#placeholder-link').href = json.qr;
  $('#placeholder-link').download = `upn-qr-${data.placilo_datum}.png`;
  
  $('#placeholder-text').textContent = 'Prenesi QR ⤵️';
  $('#placeholder-img').src = json.qr;
  $('#placeholder-img').dataset.status = 'generated';
  $('#placeholder-result').textContent = json.raw.split('\n').map((n,i) => `${String(i+1).padStart(2, '0')}  ${n}`).join('\n');

  $('#placeholder-link').classList.remove('loading');

}