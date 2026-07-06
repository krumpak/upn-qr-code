const scenariji = [
  {
    it: 'fails empty form',
    active: true,
    input: {},
    expect: {
      "errors": "Popravi vnešene napake",
      "placilo_znesek": "Znesek je obvezen.",
      "placilo_koda_namena": "Koda namena je obvezna.",
      "placilo_namen": "Namen plačila je obvezen.",
      "placilo_referenca_oznaka": "Oznaka reference je obvezna.Model reference je obvezen.Sklic je obvezen (razen pri modelu 99).",
      "placilo_referenca_model": "Oznaka reference je obvezna.Model reference je obvezen.Sklic je obvezen (razen pri modelu 99).",
      "placilo_referenca_sklic": "Oznaka reference je obvezna.Model reference je obvezen.Sklic je obvezen (razen pri modelu 99).",
      "placilo_datum": "Datum plačila je obvezen.",
      "placnik_naziv": "Naziv plačnika je obvezen.",
      "placnik_naslov": "",
      "placnik_kraj": "",
      "prejemnik_iban": "IBAN je obvezen.",
      "prejemnik_naziv": "Naziv prejemnika je obvezen.",
      "prejemnik_naslov": "Naslov prejemnika je obvezen.",
      "prejemnik_kraj": "Kraj prejemnika je obvezen.",
      "result": "n/a"
    }
  },
  {
    it: 'passes form',
    active: true,
    input: {
      placilo_znesek: 321,
      placilo_koda_namena: 'ADVA',
      placilo_namen: 'Plačilo predujma',
      placilo_referenca_oznaka: 'SI',
      placilo_referenca_model: '12',
      placilo_referenca_sklic: '43-21',
      placilo_datum: '2026-12-19',
      placnik_naziv: 'Miha Plača',
      placnik_naslov: 'Mihova 3',
      placnik_kraj: '1234 Mesto',
      prejemnik_iban: 'SI56 0000 0000 0000 000',
      prejemnik_naziv: 'Petra Prejme',
      prejemnik_naslov: 'Prejmova 21',
      prejemnik_kraj: '2345 Petrovo',
    },
    expect: {
      "errors": "",
      "placilo_znesek": "",
      "placilo_koda_namena": "",
      "placilo_namen": "",
      "placilo_referenca_oznaka": "",
      "placilo_referenca_model": "",
      "placilo_referenca_sklic": "",
      "placilo_datum": "",
      "placnik_naziv": "",
      "placnik_naslov": "",
      "placnik_kraj": "",
      "prejemnik_iban": "",
      "prejemnik_naziv": "",
      "prejemnik_naslov": "",
      "prejemnik_kraj": "",
      "result": "01  UPNQR\n02  \n03  \n04  \n05  \n06  Miha Plača\n07  Mihova 3\n08  1234 Mesto\n09  00000032100\n10  \n11  \n12  ADVA\n13  Plačilo predujma\n14  19.12.2026\n15  SI56000000000000000\n16  SI1243-21\n17  Petra Prejme\n18  Prejmova 21\n19  2345 Petrovo\n20  158"
    }
  },
  {
    it: 'fails empty form',
    active: true,
    input: {
      placilo_koda_namena: 'ADV',
    },
    expect: {
      "errors": "Popravi vnešene napake",
      "placilo_znesek": "Znesek je obvezen.",
      "placilo_koda_namena": "Koda namena mora biti dolg/-a točno 4 znakov.Koda namena mora vsebovati 4 velike črke (A-Z).",
      "placilo_namen": "Namen plačila je obvezen.",
      "placilo_referenca_oznaka": "Oznaka reference je obvezna.Model reference je obvezen.Sklic je obvezen (razen pri modelu 99).",
      "placilo_referenca_model": "Oznaka reference je obvezna.Model reference je obvezen.Sklic je obvezen (razen pri modelu 99).",
      "placilo_referenca_sklic": "Oznaka reference je obvezna.Model reference je obvezen.Sklic je obvezen (razen pri modelu 99).",
      "placilo_datum": "Datum plačila je obvezen.",
      "placnik_naziv": "Naziv plačnika je obvezen.",
      "placnik_naslov": "",
      "placnik_kraj": "",
      "prejemnik_iban": "IBAN je obvezen.",
      "prejemnik_naziv": "Naziv prejemnika je obvezen.",
      "prejemnik_naslov": "Naslov prejemnika je obvezen.",
      "prejemnik_kraj": "Kraj prejemnika je obvezen.",
      "result": "n/a"
    }
  }
];

function zagon () {
  // if (!confirm('začni UI test? Y/n')) { console.log("prekinjeno."); return; }
  console.log("starting test...");
  test();
}

// Počakaj, da so vse skripte naložene (async script.js mora priklopiti listenerje na
// #placeholder-button, sicer klik ne sproži generate()). load se sproži za async skriptami.
if (document.readyState === 'complete') {
  zagon();
} else {
  window.addEventListener('load', zagon);
}

async function test () {
  for (const scn of scenariji) {
    if (!scn.active) {
      continue;
    }

    console.log(`📋 it ${scn.it}`);
    await napolni(scn.input);
    console.log("✍️ Napolnjeno");
    clickGenerate();
    // Počakaj na dejanski rezultat: napake so izrisane sinhrono, veljaven QR pa asinhrono
    // (generate() po fetch še ~1 s čaka, nato nastavi data-status="generated").
    await waitFor(() => {
      const napake = document.querySelector('#errors').textContent.trim();
      const status = document.querySelector('#placeholder-img').dataset.status;
      return napake !== '' || status === 'generated';
    });
    const actual = investigateDom();

    const diffs = razlike(scn.expect, actual);
    const itPasses = diffs.length === 0;

    Object.assign(scn, {
      itPasses,
      actual,
      diffs,
    });

    if (itPasses) {
      console.log(...icon(scn));
    } else {
      console.log(...icon(scn, ` — ${diffs.length} razlik:`));
      diffs.forEach(d => {
        console.log(`   • ${d.key}`);
        console.log(`     expect: ${JSON.stringify(d.expect)}`);
        console.log(`     actual: ${JSON.stringify(d.actual)}`);
      });
      console.log('actual:', actual);
      console.log('expect:', scn.expect);
    }
    console.log(`-----`);
  }

  // pregled testov
  console.log("PREGLED TESTOV");

  for (const scn of scenariji) {
    if (!scn.active) {
      continue;
    }

    console.log(...icon(scn));
  }

  const allTests = scenariji.filter(scn => scn.active).length;
  const passedTests = scenariji.filter(scn => scn.itPasses === true && scn.active).length;
  const failedTests = scenariji.filter(scn => scn.itPasses === false && scn.active).length;

  console.log("-----");

  console.log(failedTests > 0 ? `❌ ${failedTests}/${allTests} testov neuspešnih` : `✅ Vsi testi uspešni (${passedTests}/${allTests})`);
}

// Vrne argumente za console.log z barvno obarvano kljukico (%c + CSS; brskalniška konzola
// ANSI kod ne izriše). Uporaba: console.log(...icon(scn)) ali console.log(...icon(scn, ' …')).
function icon (scn, suffix = '') {
  const ok = scn.itPasses;
  return [
    `%c${ok ? '✓' : '✗'}%c it ${scn.it}${suffix}`,
    `color: ${ok ? '#2ecc40' : '#ff4136'}; font-weight: bold`,
    '',
  ];
}

// Primerja expect/actual po ključih (neodvisno od vrstnega reda) in vrne le razlike.
function razlike (expect, actual) {
  const kljuci = new Set([...Object.keys(expect), ...Object.keys(actual)]);
  const diffs = [];
  for (const key of kljuci) {
    const e = expect[key] ?? '';
    const a = actual[key] ?? '';
    if (e !== a) diffs.push({ key, expect: e, actual: a });
  }
  return diffs;
}

async function napolni (input) {
  // Najprej izprazni vsa polja iz pravil (odklop od HTML privzetkov, npr. datum),
  // šele nato nastavi vrednosti iz scenarija — vsak scenarij tako določa poln vhod.
  Object.keys(window.UPN_RULES ?? {}).forEach((key) => {
    const node = document.querySelector(`[name="${key}"]`);
    if (node) node.value = '';
  });

  Object.keys(input).forEach((key) => {
    const node = document.querySelector(`[name="${key}"]`);
    if (node) node.value = input[key];
  });
}

function clickGenerate () {
  document.querySelector("#placeholder-button").click();
  console.log("👆 Generate");
}

function investigateDom () {
  console.log("🔍 Preverjam");

  return {
    errors: document.querySelector("#errors").textContent,
    placilo_znesek: document.querySelector(`[name="placilo_znesek"] ~ .error`).textContent,
    placilo_koda_namena: document.querySelector(`[name="placilo_koda_namena"] ~ .error`).textContent,
    placilo_namen: document.querySelector(`[name="placilo_namen"] ~ .error`).textContent,
    placilo_referenca_oznaka: document.querySelector(`[name="placilo_referenca_oznaka"] ~ .error`).textContent,
    placilo_referenca_model: document.querySelector(`[name="placilo_referenca_model"] ~ .error`).textContent,
    placilo_referenca_sklic: document.querySelector(`[name="placilo_referenca_sklic"] ~ .error`).textContent,
    placilo_datum: document.querySelector(`[name="placilo_datum"] ~ .error`).textContent,
    placnik_naziv: document.querySelector(`[name="placnik_naziv"] ~ .error`).textContent,
    placnik_naslov: document.querySelector(`[name="placnik_naslov"] ~ .error`).textContent,
    placnik_kraj: document.querySelector(`[name="placnik_kraj"] ~ .error`).textContent,
    prejemnik_iban: document.querySelector(`[name="prejemnik_iban"] ~ .error`).textContent,
    prejemnik_naziv: document.querySelector(`[name="prejemnik_naziv"] ~ .error`).textContent,
    prejemnik_naslov: document.querySelector(`[name="prejemnik_naslov"] ~ .error`).textContent,
    prejemnik_kraj: document.querySelector(`[name="prejemnik_kraj"] ~ .error`).textContent,
    result: document.querySelector(`#placeholder-result`).textContent
  };
}

// Počaka, dokler predikat ni resničen (ali do timeouta). Na timeout se resolve (ne throw),
// da test ne obvisi — investigateDom nato prebere dejansko stanje, diff prijavi neujemanje.
function waitFor (predicate, { timeout = 5000, interval = 50 } = {}) {
  return new Promise((resolve) => {
    const start = Date.now();
    (function poll () {
      if (predicate() || Date.now() - start >= timeout) return resolve();
      setTimeout(poll, interval);
    })();
  });
}