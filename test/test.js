// Datum je v obliki Y-m-d (input type=date); IBAN s presledki (client normalizira).
const VELJAVEN = {
  placilo_znesek: '10.00',
  placilo_datum: '2026-12-19',
  placilo_koda_namena: 'GDSV',
  placilo_namen: 'Test plačilo',
  placilo_referenca_oznaka: 'SI',
  placilo_referenca_model: '00',
  placilo_referenca_sklic: '12345',
  placnik_naziv: 'Test Plačnik',
  placnik_naslov: 'Ulica 1',
  placnik_kraj: '1000 Ljubljana',
  prejemnik_iban: 'SI56 0203 6025 3863 406',
  prejemnik_naziv: 'Test Prejemnik',
  prejemnik_naslov: 'Cesta 2',
  prejemnik_kraj: '2000 Maribor',
};

// Pričakovani 'result' nizi za veljavne scenarije (izračunani prek dejanskega generate.php).
const RESULT_BASE        = "01  UPNQR\n02  \n03  \n04  \n05  \n06  Test Plačnik\n07  Ulica 1\n08  1000 Ljubljana\n09  00000001000\n10  \n11  \n12  GDSV\n13  Test plačilo\n14  19.12.2026\n15  SI56020360253863406\n16  SI0012345\n17  Test Prejemnik\n18  Cesta 2\n19  2000 Maribor\n20  157";
const RESULT_MODEL99     = "01  UPNQR\n02  \n03  \n04  \n05  \n06  Test Plačnik\n07  Ulica 1\n08  1000 Ljubljana\n09  00000001000\n10  \n11  \n12  GDSV\n13  Test plačilo\n14  19.12.2026\n15  SI56020360253863406\n16  SI99\n17  Test Prejemnik\n18  Cesta 2\n19  2000 Maribor\n20  152";
const RESULT_OPT_PLACNIK = "01  UPNQR\n02  \n03  \n04  \n05  \n06  Test Plačnik\n07  \n08  \n09  00000001000\n10  \n11  \n12  GDSV\n13  Test plačilo\n14  19.12.2026\n15  SI56020360253863406\n16  SI0012345\n17  Test Prejemnik\n18  Cesta 2\n19  2000 Maribor\n20  136";

// Prazna predloga pričakovanega izida (vsa polja brez napake).
const PRAZNO = {
  errors: '',
  placilo_znesek: '', placilo_koda_namena: '', placilo_namen: '',
  placilo_referenca_oznaka: '', placilo_referenca_model: '', placilo_referenca_sklic: '',
  placilo_datum: '',
  placnik_naziv: '', placnik_naslov: '', placnik_kraj: '',
  prejemnik_iban: '', prejemnik_naziv: '', prejemnik_naslov: '', prejemnik_kraj: '',
  result: 'n/a',
};

// napake(): scenarij z napakami — poda se le polja z napako (ostala ostanejo prazna).
function napake (polja) {
  return { ...PRAZNO, errors: 'Popravi vnešene napake', ...polja };
}
// veljavno(): scenarij brez napak — poda se le pričakovani 'result'.
function veljavno (result) {
  return { ...PRAZNO, result };
}
// ref(): oznaka/model/sklic si delijo isti .error span → vsem trem enako (združeno) sporočilo.
function ref (msg) {
  return { placilo_referenca_oznaka: msg, placilo_referenca_model: msg, placilo_referenca_sklic: msg };
}

const scenariji = [
  /* ═══ VELJAVNI VNOSI ═══════════════════════════════════ */
  { it: 'znesek brez decimalk (10 → normalizira v 10.00)', active: true, input: { ...VELJAVEN, placilo_znesek: '10' }, expect: veljavno(RESULT_BASE) },
  { it: 'model 99 + prazen sklic → OK (optionalIfModel)', active: true, input: { ...VELJAVEN, placilo_referenca_model: '99', placilo_referenca_sklic: '' }, expect: veljavno(RESULT_MODEL99) },
  { it: 'neobvezni polji plačnika prazni (naslov, kraj) → OK', active: true, input: { ...VELJAVEN, placnik_naslov: '', placnik_kraj: '' }, expect: veljavno(RESULT_OPT_PLACNIK) },

  /* ═══ PRAZEN OBRAZEC (vsa obvezna polja) ═══════════════ */
  {
    it: 'prazen obrazec → vse obvezne napake',
    active: true,
    input: {},
    expect: napake({
      placilo_znesek: 'Znesek je obvezen.',
      placilo_koda_namena: 'Koda namena je obvezna.',
      placilo_namen: 'Namen plačila je obvezen.',
      placilo_datum: 'Datum plačila je obvezen.',
      ...ref('Oznaka reference je obvezna.Model reference je obvezen.Sklic je obvezen (razen pri modelu 99).'),
      placnik_naziv: 'Naziv plačnika je obvezen.',
      prejemnik_iban: 'IBAN je obvezen.',
      prejemnik_naziv: 'Naziv prejemnika je obvezen.',
      prejemnik_naslov: 'Naslov prejemnika je obvezen.',
      prejemnik_kraj: 'Kraj prejemnika je obvezen.',
    }),
  },

  /* ═══ ZNESEK ═══════════════════════════════════════════ */
  { it: 'znesek prazen', active: true, input: { ...VELJAVEN, placilo_znesek: '' }, expect: napake({ placilo_znesek: 'Znesek je obvezen.' }) },
  { it: 'znesek napačna oblika (abc)', active: true, input: { ...VELJAVEN, placilo_znesek: 'abc' }, expect: napake({ placilo_znesek: 'Znesek je obvezen.' }) },
  { it: 'znesek = 0', active: true, input: { ...VELJAVEN, placilo_znesek: '0' }, expect: napake({ placilo_znesek: 'Znesek mora biti večji od 0.' }) },
  { it: 'znesek previsok', active: true, input: { ...VELJAVEN, placilo_znesek: '1000000000.00' }, expect: napake({ placilo_znesek: 'Znesek je previsok (največ 999.999.999,99 EUR).' }) },

  /* ═══ DATUM ════════════════════════════════════════════ */
  { it: 'datum prazen', active: true, input: { ...VELJAVEN, placilo_datum: '' }, expect: napake({ placilo_datum: 'Datum plačila je obvezen.' }) },
  { it: 'datum v preteklosti', active: true, input: { ...VELJAVEN, placilo_datum: '2020-01-01' }, expect: napake({ placilo_datum: 'Datum plačila ne sme biti v preteklosti.' }) },

  /* ═══ KODA NAMENA ══════════════════════════════════════ */
  { it: 'koda prazna', active: true, input: { ...VELJAVEN, placilo_koda_namena: '' }, expect: napake({ placilo_koda_namena: 'Koda namena je obvezna.' }) },
  { it: 'koda prekratka (ADV) → dolžina + vzorec', active: true, input: { ...VELJAVEN, placilo_koda_namena: 'ADV' }, expect: napake({ placilo_koda_namena: 'Koda namena mora biti dolg/-a točno 4 znakov.Koda namena mora vsebovati 4 velike črke (A-Z).' }) },
  { it: 'koda z nedovoljenimi znaki (GD12)', active: true, input: { ...VELJAVEN, placilo_koda_namena: 'GD12' }, expect: napake({ placilo_koda_namena: 'Koda namena mora vsebovati 4 velike črke (A-Z).' }) },

  /* ═══ NAMEN PLAČILA ════════════════════════════════════ */
  { it: 'namen prazen', active: true, input: { ...VELJAVEN, placilo_namen: '' }, expect: napake({ placilo_namen: 'Namen plačila je obvezen.' }) },
  { it: 'namen predolg (43 znakov)', active: true, input: { ...VELJAVEN, placilo_namen: 'A'.repeat(43) }, expect: napake({ placilo_namen: 'Namen plačila je predolg/-a (največ 42 znakov).' }) },

  /* ═══ REFERENCA (oznaka / model / sklic) ═══════════════ */
  { it: 'oznaka prazna', active: true, input: { ...VELJAVEN, placilo_referenca_oznaka: '' }, expect: napake(ref('Oznaka reference je obvezna.')) },
  { it: 'oznaka neveljavna (XX)', active: true, input: { ...VELJAVEN, placilo_referenca_oznaka: 'XX' }, expect: napake(ref('Oznaka reference je lahko le SI ali RF.')) },
  { it: 'model prazen', active: true, input: { ...VELJAVEN, placilo_referenca_model: '' }, expect: napake(ref('Model reference je obvezen.')) },
  { it: 'model prekratek (1) → dolžina + vzorec', active: true, input: { ...VELJAVEN, placilo_referenca_model: '1' }, expect: napake(ref('Model reference mora biti dolg/-a točno 2 znakov.Model reference morata biti 2 števki.')) },
  { it: 'model nenumeričen (AB)', active: true, input: { ...VELJAVEN, placilo_referenca_model: 'AB' }, expect: napake(ref('Model reference morata biti 2 števki.')) },
  { it: 'sklic prazen (model 00)', active: true, input: { ...VELJAVEN, placilo_referenca_sklic: '' }, expect: napake(ref('Sklic je obvezen (razen pri modelu 99).')) },
  { it: 'sklic z nedovoljenimi znaki', active: true, input: { ...VELJAVEN, placilo_referenca_sklic: 'abc!@#' }, expect: napake(ref('Referenca lahko vsebuje le črke, številke in vezaje (-).')) },
  { it: 'sklic predolg (23 znakov)', active: true, input: { ...VELJAVEN, placilo_referenca_sklic: '1'.repeat(23) }, expect: napake(ref('Sklic je predolg/-a (največ 22 znakov).')) },

  /* ═══ PLAČNIK ══════════════════════════════════════════ */
  { it: 'plačnik naziv prazen', active: true, input: { ...VELJAVEN, placnik_naziv: '' }, expect: napake({ placnik_naziv: 'Naziv plačnika je obvezen.' }) },
  { it: 'plačnik naziv predolg (34)', active: true, input: { ...VELJAVEN, placnik_naziv: 'A'.repeat(34) }, expect: napake({ placnik_naziv: 'Naziv plačnika je predolg/-a (največ 33 znakov).' }) },
  { it: 'plačnik naslov predolg (34)', active: true, input: { ...VELJAVEN, placnik_naslov: 'A'.repeat(34) }, expect: napake({ placnik_naslov: 'Naslov plačnika je predolg/-a (največ 33 znakov).' }) },

  /* ═══ PREJEMNIK ════════════════════════════════════════ */
  { it: 'prejemnik naziv prazen', active: true, input: { ...VELJAVEN, prejemnik_naziv: '' }, expect: napake({ prejemnik_naziv: 'Naziv prejemnika je obvezen.' }) },
  { it: 'prejemnik naziv predolg (34)', active: true, input: { ...VELJAVEN, prejemnik_naziv: 'A'.repeat(34) }, expect: napake({ prejemnik_naziv: 'Naziv prejemnika je predolg/-a (največ 33 znakov).' }) },
  { it: 'prejemnik naslov prazen', active: true, input: { ...VELJAVEN, prejemnik_naslov: '' }, expect: napake({ prejemnik_naslov: 'Naslov prejemnika je obvezen.' }) },
  { it: 'prejemnik kraj prazen', active: true, input: { ...VELJAVEN, prejemnik_kraj: '' }, expect: napake({ prejemnik_kraj: 'Kraj prejemnika je obvezen.' }) },

  /* ═══ IBAN ═════════════════════════════════════════════ */
  { it: 'iban prazen', active: true, input: { ...VELJAVEN, prejemnik_iban: '' }, expect: napake({ prejemnik_iban: 'IBAN je obvezen.' }) },
  { it: 'iban napačna oblika (DE)', active: true, input: { ...VELJAVEN, prejemnik_iban: 'DE89370400440532013000' }, expect: napake({ prejemnik_iban: 'IBAN ni pravilne oblike (SI + 17 številk).' }) },
  { it: 'iban prekratek (SI00)', active: true, input: { ...VELJAVEN, prejemnik_iban: 'SI00' }, expect: napake({ prejemnik_iban: 'IBAN ni pravilne oblike (SI + 17 številk).' }) },

  // Osnovni VELJAVEN vnos — za izolirano testiranje posameznega polja (prelomimo eno polje).
  { it: 'veljaven osnovni vnos', active: true, input: { ...VELJAVEN }, expect: veljavno(RESULT_BASE) },
  {
    it: 'veljaven vnos (Miha/Petra)',
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
    expect: veljavno("01  UPNQR\n02  \n03  \n04  \n05  \n06  Miha Plača\n07  Mihova 3\n08  1234 Mesto\n09  00000032100\n10  \n11  \n12  ADVA\n13  Plačilo predujma\n14  19.12.2026\n15  SI56000000000000000\n16  SI1243-21\n17  Petra Prejme\n18  Prejmova 21\n19  2345 Petrovo\n20  158"),
  },
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
  const totalTests = scenariji.filter(s => s.active).length;
  let countTest = 0;
  for (const scn of scenariji) {
    if (!scn.active) {
      continue;
    }

    countTest++;
    console.log(`📋 [${countTest}/${totalTests}] it ${scn.it}`);
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

    await delay(2);
  }

  // pregled testov
  console.log("PREGLED TESTOV");

  for (const scn of scenariji) {
    if (!scn.active) {
      continue;
    }

    console.log(...icon(scn));
  }

  const passedTests = scenariji.filter(scn => scn.itPasses === true && scn.active).length;
  const failedTests = scenariji.filter(scn => scn.itPasses === false && scn.active).length;

  console.log("-----");

  console.log(failedTests > 0 ? `❌ ${failedTests}/${totalTests} testov neuspešnih` : `✅ Vsi testi uspešni (${passedTests}/${totalTests})`);
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

// Pavza N sekund (za opazovanje poteka v brskalniku).
function delay (seconds = 1) {
  return new Promise(resolve => setTimeout(resolve, seconds * 1000));
}