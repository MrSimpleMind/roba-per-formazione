(function(){
  const apiBase = protocolliSearch.apiURL;
  const searchInput = document.getElementById('protocollo-search-input');
  const unitaSelect = document.getElementById('protocollo-filter-unita');
  const profiloSelect = document.getElementById('protocollo-filter-profilo');
  const resultsContainer = document.getElementById('protocollo-results');

  if(!resultsContainer) return;

  function populateSelect(select, terms){
    terms.forEach(term => {
      const opt = document.createElement('option');
      opt.value = term.id;
      opt.textContent = term.name;
      select.appendChild(opt);
    });
  }

  function fetchTerms(){
    const udo = fetch(`${apiBase}/unita_di_offerta?per_page=100`).then(r => r.json());
    const prof = fetch(`${apiBase}/profili_professionali?per_page=100`).then(r => r.json());
    return Promise.all([udo, prof]).then(([udoTerms, profTerms]) => {
      populateSelect(unitaSelect, udoTerms);
      populateSelect(profiloSelect, profTerms);
    });
  }

  function renderResults(items){
    resultsContainer.innerHTML = '';
    if(!items || items.length === 0){
      resultsContainer.innerHTML = '<p>Nessun protocollo trovato.</p>';
      return;
    }
    const list = document.createElement('ul');
    list.className = 'protocollo-list';
    items.forEach(item => {
      const li = document.createElement('li');
      const link = document.createElement('a');
      link.href = item.link;
      link.textContent = item.title.rendered;
      li.appendChild(link);
      list.appendChild(li);
    });
    resultsContainer.appendChild(list);
  }

  function fetchResults(){
    const params = new URLSearchParams();
    if(searchInput.value.trim()) params.set('search', searchInput.value.trim());
    if(unitaSelect.value) params.set('unita_di_offerta', unitaSelect.value);
    if(profiloSelect.value) params.set('profili_professionali', profiloSelect.value);
    params.set('per_page', 20);

    fetch(`${apiBase}/protocollo?${params.toString()}`)
      .then(r => r.json())
      .then(renderResults)
      .catch(() => { resultsContainer.innerHTML = '<p>Errore nel caricamento.</p>'; });
  }

  searchInput.addEventListener('input', fetchResults);
  unitaSelect.addEventListener('change', fetchResults);
  profiloSelect.addEventListener('change', fetchResults);

  fetchTerms().then(fetchResults);
})();
