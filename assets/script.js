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

$('#placeholder-button').addEventListener('click', generate);
$('#placeholder-img').addEventListener('click', generate);

async function generate (event) {
  if ($('#placeholder-img').dataset.status === 'generated') {
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
  if (data.placilo_referenca && !/^[A-Z0-9\-]+$/i.test(data.placilo_referenca)) {
    $('[name=placilo_referenca] + .error').textContent = 'Referenca lahko vsebuje le črke, številke in vezaj (-).';
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
  
  $('#placeholder-text').textContent = 'Prenosi QR ⤵️';
  $('#placeholder-img').src = json.qr;
  $('#placeholder-img').dataset.status = 'generated';
  $('#placeholder-result').textContent = json.raw;

  $('#placeholder-link').classList.remove('loading');

}