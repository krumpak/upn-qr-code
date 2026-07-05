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

  // Pravila iz PHP-ja (validation.php → window.UPN_RULES); enotni vir resnice.
  const RULES = window.UPN_RULES || {};
  // Polja s posebno logiko obravnavamo ločeno; ostala gredo skozi generično zanko.
  const BESPOKE = new Set(['placilo_znesek', 'placilo_datum', 'prejemnik_iban']);

  // ZNESEK (posebna, prizanesljiva logika: dovoli 0–2 decimalki, normaliziraj s toFixed pred pošiljanjem)
  const rZnesek = RULES.placilo_znesek || {};
  const znesek = String(data.placilo_znesek).replace(',', '.').trim();
  if (!znesek || znesek === 'NaN') {
    addError('placilo_znesek', rZnesek.requiredMsg || 'Znesek je obvezen.');
  } else if (!/^\d+(\.\d{1,2})?$/.test(znesek)) {
    addError('placilo_znesek', rZnesek.patternMsg || 'Znesek ni v pravilni obliki.');
  } else {
    if (parseFloat(znesek) <= (rZnesek.numGt ?? 0))
      addError('placilo_znesek', rZnesek.numGtMsg || 'Znesek mora biti večji od 0.');
    if (rZnesek.numLte != null && parseFloat(znesek) > rZnesek.numLte)
      addError('placilo_znesek', rZnesek.numLteMsg || 'Znesek je previsok.');
  }

  // DATUM (posebna logika: HTML input type=date daje obliko Y-m-d)
  const rDatum = RULES.placilo_datum || {};
  if (!data.placilo_datum) {
    addError('placilo_datum', rDatum.requiredMsg || 'Datum plačila je obvezen.');
  } else {
    const parsedDatum = new Date(data.placilo_datum);
    if (isNaN(parsedDatum)) {
      addError('placilo_datum', rDatum.formatMsg || 'Datum plačila ni v pravilni obliki.');
    } else if (rDatum.notPast) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (parsedDatum < today)
        addError('placilo_datum', rDatum.notPastMsg || 'Datum plačila ne sme biti v preteklosti.');
    }
  }

  // GENERIČNA ZANKA — besedilna in referenčna polja (isti vrstni red kot PHP upn_validate:
  // required → exactlen → maxlen → enum → pattern). ISO-8859-2 preverja le strežnik.
  for (const [field, r] of Object.entries(RULES)) {
    if (BESPOKE.has(field)) continue;
    let val = String(data[field] ?? '').trim();
    const errKey = r.errorKey || field;
    if (r.uppercase) val = val.toUpperCase();

    let required = !!r.required;
    if (required && r.optionalIfModel) {
      const model = String(data.placilo_referenca_model ?? '').trim().toUpperCase();
      if (model === r.optionalIfModel) required = false;
    }
    if (required && val === '') {
      addError(errKey, r.requiredMsg || `${r.label} je obvezen/-na.`);
      continue;
    }
    if (val === '') continue;

    if (r.exactlen != null && val.length !== r.exactlen)
      addError(errKey, `${r.label} mora biti dolg/-a točno ${r.exactlen} znakov.`);
    if (r.maxlen != null && val.length > r.maxlen)
      addError(errKey, `${r.label} je predolg/-a (največ ${r.maxlen} znakov).`);
    if (Array.isArray(r.enum) && !r.enum.includes(val))
      addError(errKey, r.enumMsg || `${r.label} ni veljavna vrednost.`);
    if (r.pattern && !new RegExp(r.pattern, r.flags || '').test(val))
      addError(errKey, r.patternMsg || `${r.label} ni v pravilni obliki.`);
  }

  // IBAN (posebna logika: normalizacija presledkov + velike črke, nato vzorec)
  const rIban = RULES.prejemnik_iban || {};
  const iban = data.prejemnik_iban.replace(/\s+/g, '').toUpperCase();
  if (!iban) {
    addError('prejemnik_iban', rIban.requiredMsg || 'IBAN je obvezen.');
  } else if (rIban.pattern && !new RegExp(rIban.pattern).test(iban)) {
    addError('prejemnik_iban', rIban.patternMsg || 'IBAN ni pravilne oblike (SI + 17 številk).');
  }

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
