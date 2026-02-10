
(async() => {
  try {
    if (localStorage.getItem('edavki') == null) {
      localStorage.setItem('edavki', null);
    }

    const token = localStorage.getItem('edavki');

    const response = await fetch('./edavki.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        action: 'auth'
      })
    })
    const result = await response.json();

    if (result.code === 403) {
      $('#login-form').classList.remove('hidden');
      $('#content').classList.add('hidden');
      throw new Error(result.message);
    }

    if (!result.success) {
      throw new Error(result.message);
    }

    $('#login-form').classList.add('hidden');
    $('#content').classList.remove('hidden');
  } catch (err) {
    console.error(err);
  }

  $('#generate').addEventListener('click', async (e) => {
    try {
      const response = await fetch('./edavki.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('edavki')}`,
        },
        body: JSON.stringify({
          action: 'generate'
        })
      })
      const result = await response.json();

      if (!result.success) {
        alert(result.message);
        throw new Error(result.message);
      }

      const { data } = result;

      const preparedData = [];
      Object.keys(data).forEach(key => {
        const val = data[key];
        preparedData.push({
          label: key,
          img: val.img_name,
          placilo_datum: val.rok,
          placilo_namen: val.namen,
          placilo_koda_namena: val.koda,
          placilo_referenca: val.referenca,
          placilo_znesek: val.znesek,
          placnik_kraj: val.placnik.kraj,
          placnik_naslov: val.placnik.naslov,
          placnik_naziv: val.placnik.naziv,
          prejemnik_iban: val.iban,
          prejemnik_kraj: val.prejemnik.kraj,
          prejemnik_naslov: val.prejemnik.naslov,
          prejemnik_naziv: val.prejemnik.naziv,
        });
      });
        
      console.log('preparedData', preparedData)

      const promises = preparedData.map(async (bill) => {
        const res = await fetch('generate.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(bill)
        });

        const json = await res.json();
        
        if (json.error) {
          Object.assign(bill, { success: false, error: json.error });
          return bill;
        }

        Object.assign(bill, json, { success: true, filename: bill.label.replace(/ /gi, '_') });
        return bill;
      });

      const res = await Promise.all(promises);

      $('#result').innerHTML = res.map((bill, i) => {

        setTimeout(() => {
          $('.qr-link')[i].click();
        }, 50 * i)

        return `<div class="card">
          <h2>${bill.label}</h2>
          <b>${bill.placilo_namen}</b><br>
          [ ${bill.placilo_namena_koda} ] <b>${bill.placilo_znesek} EUR</b><br>
          IBAN: <b>${bill.prejemnik_iban.replace(/^(.{4})(.{4})(.{4})(.{4})(.*)$/, '$1 $2 $3 $4 $5')}</b><br>
          referenca: <b>${bill.placilo_referenca.replace(/^(.{4})(.*)$/, '$1 $2')}</b><br>
          rok plačila: <b>${bill.placilo_datum.replace(/(\d{2})(\d{2})(\d{4})/, '$1.$2.$3')}</b><br>
          <br>
          <a class="qr-link" href="${bill.qr}" download="upn_${bill.img}_0${i+1}_${bill.filename}.png"><img src="${bill.qr}" alt="${bill.label}"></a>
        </div>`;

      }).join('\n');

      setTimeout(() => {}, 100)

    } catch (err) {
      console.error(err);
    }
  });

  $('#login').addEventListener('click', (e) => {
    const token = $('#token').value;
    if (!token) {
      alert('Žeton manjka.');
      return;
    }
    localStorage.setItem('edavki', token);
    window.location.reload();
  });

  $('#logout').addEventListener('click', (e) => {
    localStorage.setItem('edavki', null);
    $('#login-form').classList.remove('hidden');
    $('#content').classList.add('hidden');
  });
  
  function $(sel) {
    if (sel.startsWith('#'))
      return document.getElementById(sel.slice(1));
    else if (sel.startsWith('.')) {
      const node = document.querySelectorAll(sel);
      if (node.length > 0) {
        return node;
      } else {
        return document.querySelector(sel); 
      }
    }
    return null;
  }
})();