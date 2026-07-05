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

function renderErrors(errors) {
  $('.error').forEach(el => el.innerHTML = '');
  $('#errors').textContent = '';
  Object.keys(errors).forEach(field => {
    const el = $(`[name=${field}] ~ .error`);
    if (!el) return;
    const msgs = errors[field];
    el.innerHTML = msgs.length === 1
      ? msgs[0]
      : '<ul>' + msgs.map(m => `<li>${m}</li>`).join('') + '</ul>';
  });
  const hasErrors = Object.keys(errors).length > 0;
  if (hasErrors) $('#errors').textContent = 'Popravi vnešene napake';
  return hasErrors;
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
  const data = Object.fromEntries(new FormData(form));
  const errors = {};
  const addError = (field, msg) => { (errors[field] ??= []).push(msg); };

  // ZNESEK
  // Payload ima znesek zapisan kot 11-mestno število centov → max 999.999.999,99 EUR.
  const MAX_ZNESEK = 999999999.99;
  const znesek = String(data.placilo_znesek).replace(',', '.');
  if (!znesek || znesek === 'NaN') {
    addError('placilo_znesek', 'Znesek je obvezen.');
  } else if (!/^\d+(\.\d{1,2})?$/.test(znesek)) {
    addError('placilo_znesek', 'Znesek ni v pravilni obliki.');
  } else {
    if (parseFloat(znesek) <= 0)
      addError('placilo_znesek', 'Znesek mora biti večji od 0.');
    if (parseFloat(znesek) > MAX_ZNESEK)
      addError('placilo_znesek', 'Znesek je previsok (največ 999.999.999,99 EUR).');
  }

  // DATUM
  if (!data.placilo_datum) {
    addError('placilo_datum', 'Datum plačila je obvezen.');
  } else {
    const parsedDatum = new Date(data.placilo_datum);
    if (isNaN(parsedDatum))
      addError('placilo_datum', 'Datum plačila ni v pravilni obliki.');
    if (!isNaN(parsedDatum)) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (parsedDatum < today) addError('placilo_datum', 'Datum plačila ne sme biti v preteklosti.');
    }
  }

  // OBVEZNA POLJA
  if (!data.placilo_koda_namena) addError('placilo_koda_namena', 'Koda namena je obvezna.');
  if (!data.placilo_namen) addError('placilo_namen', 'Namen plačila je obvezen.');
  if (!data.placilo_referenca_oznaka) addError('placilo_referenca_oznaka', 'Oznaka reference je obvezna.');
  if (!data.placilo_referenca_model) addError('placilo_referenca_oznaka', 'Model reference je obvezen.');
  if (!data.placilo_referenca_sklic) addError('placilo_referenca_oznaka', 'Sklic je obvezen.');
  if (!data.placnik_naziv) addError('placnik_naziv', 'Naziv plačnika je obvezen.');
  if (!data.prejemnik_naziv) addError('prejemnik_naziv', 'Naziv prejemnika je obvezen.');

  // REFERENCA SKLIC
  if (data.placilo_referenca_sklic && !/^[A-Z0-9\-]+$/i.test(data.placilo_referenca_sklic))
    addError('placilo_referenca_oznaka', 'Referenca lahko vsebuje le črke, številke in vezaje (-).');

  // IBAN
  const iban = data.prejemnik_iban.replace(/\s+/g, '').toUpperCase();
  if (!iban) addError('prejemnik_iban', 'IBAN je obvezen.');
  else if (!/^SI\d{17}$/.test(iban)) addError('prejemnik_iban', 'IBAN ni pravilne oblike (SI + 17 številk).');

  if (renderErrors(errors)) {
    $('#placeholder-link').classList.remove('loading');
    return;
  }

  // FORMAT TRANSFORMACIJE
  data.placilo_znesek = Number(znesek).toFixed(2);
  data.placilo_datum = data.placilo_datum.split('-').reverse().join('.');
  data.prejemnik_iban = iban;

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

  // RATE LIMIT (429)
  if (res.status === 429) {
    alert(json.error);
    $('#placeholder-link').classList.remove('loading');
    return;
  }

  if (json.errors) {
    Object.entries(json.errors).forEach(([field, msgs]) => {
      msgs.forEach(msg => addError(field, msg));
    });
    renderErrors(errors);
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
