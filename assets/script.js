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

const STORAGE_KEY = 'upn_presets';
const getPresets = () => JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
const setPresets = (p) => localStorage.setItem(STORAGE_KEY, JSON.stringify(p));

function applyData(data) {
  Object.keys(data).forEach(key => {
    const el = $(`[name=${key}]`);
    if (el) el.value = data[key];
  });
}

function refreshPresetSelect() {
  const sel = $('#preset-select');
  const presets = getPresets();
  sel.innerHTML = '<option value="" selected disabled>— izberi shranjen vnos —</option>'
    + presets.map((p, i) => `<option value="${i}">${p.name}</option>`).join('');
}

refreshPresetSelect();

$('#preset-save').addEventListener('click', () => {
  const ime = prompt('Ime preset-a?');
  if (!ime) return;
  const presets = getPresets();
  presets.unshift({ name: ime, data: Object.fromEntries(new FormData($('#qrForm'))) });
  setPresets(presets);
  refreshPresetSelect();
  $('#preset-select').value = '0';
});

$('#preset-select').addEventListener('change', () => {
  const sel = $('#preset-select');
  if (sel.value === '') return;
  applyData(getPresets()[sel.value].data);
});

$('#preset-delete').addEventListener('click', () => {
  const sel = $('#preset-select');
  if (sel.value === '') return;
  const presets = getPresets();
  if (!confirm(`Izbriši "${presets[sel.value].name}"?`)) return;
  presets.splice(sel.value, 1);
  setPresets(presets);
  refreshPresetSelect();
});

$('#form-clear').addEventListener('click', () => {
  $('#qrForm').reset();
});

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