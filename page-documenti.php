<?php
/**
 * Template per la pagina /documenti/ - La Meridiana
 * Sistema di ricerca unificata per Protocolli, Moduli e Pianificazione ATS
 * 
 * NOTA: Tutti gli stili sono nel file CSS centralizzato
 * /assets/css/meridiana-design-system.css
 * 
 * @version 1.0.0
 */

get_header(); ?>

<div class="meridiana-page-container">
    
    <!-- Header pagina -->
    <header class="meridiana-page-header">
        <div class="page-header-content">
            <h1 class="meridiana-page-title">📚 Ricerca Documenti</h1>
            <p class="meridiana-page-description">
                Trova rapidamente protocolli, moduli e documentazione aziendale. 
                Utilizza i filtri per affinare la ricerca.
            </p>
            
            <!-- Stats rapide -->
            <?php 
            $counts = meridiana_get_document_counts();
            if ($counts['totale'] > 0): ?>
                <div class="quick-stats meridiana-flex-center meridiana-mt-lg" style="gap: var(--meridiana-spacing-xl); flex-wrap: wrap;">
                    <span class="stat-item meridiana-p-sm" style="background: var(--meridiana-bg-light); border-radius: 20px; font-size: var(--meridiana-font-size-base); border: 1px solid var(--meridiana-border);">
                        📄 <?php echo $counts['protocolli']; ?> Protocolli
                    </span>
                    <span class="stat-item meridiana-p-sm" style="background: var(--meridiana-bg-light); border-radius: 20px; font-size: var(--meridiana-font-size-base); border: 1px solid var(--meridiana-border);">
                        📋 <?php echo $counts['moduli']; ?> Moduli
                    </span>
                    <span class="stat-item meridiana-p-sm" style="background: var(--meridiana-bg-light); border-radius: 20px; font-size: var(--meridiana-font-size-base); border: 1px solid var(--meridiana-border);">
                        ⚠️ <?php echo $counts['pianificazione']; ?> Pianificazione ATS
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Sistema filtri avanzati -->
    <div class="meridiana-card meridiana-mb-2xl">
        <form id="documenti-search-form" class="search-filters-form meridiana-grid meridiana-grid-auto" style="align-items: end;">
            
            <!-- Ricerca testuale -->
            <div class="meridiana-form-group">
                <label for="search-text" class="meridiana-form-label">
                    🔍 Cerca nei documenti
                </label>
                <input 
                    type="text" 
                    id="search-text" 
                    name="search_text" 
                    placeholder="Scrivi qui per cercare..."
                    class="meridiana-form-input"
                    autocomplete="off"
                    value="<?php echo isset($_GET['search_text']) ? esc_attr($_GET['search_text']) : ''; ?>"
                >
                <small class="meridiana-form-help">Cerca nel titolo, contenuto e descrizione</small>
            </div>

            <!-- Filtri per tipo contenuto -->
            <div class="meridiana-form-group">
                <span class="meridiana-form-label">📁 Tipo di documento</span>
                <div class="meridiana-checkbox-group">
                    <label class="meridiana-checkbox-label">
                        <input type="checkbox" name="content_types[]" value="protocollo" checked class="meridiana-checkbox-input">
                        <span class="meridiana-checkbox-custom"></span>
                        Protocolli
                    </label>
                    <label class="meridiana-checkbox-label">
                        <input type="checkbox" name="content_types[]" value="modulo" checked class="meridiana-checkbox-input">
                        <span class="meridiana-checkbox-custom"></span>
                        Moduli
                    </label>
                    <label class="meridiana-checkbox-label">
                        <input type="checkbox" name="content_types[]" value="pianificazione" checked class="meridiana-checkbox-input">
                        <span class="meridiana-checkbox-custom"></span>
                        Pianificazione ATS
                    </label>
                </div>
            </div>

            <!-- Filtro UDO -->
            <div class="meridiana-form-group">
                <label for="filter-udo" class="meridiana-form-label">
                    🏢 Unità di Offerta
                </label>
                <select id="filter-udo" name="unita_di_offerta" class="meridiana-form-select">
                    <option value="">Tutte le unità</option>
                    <?php 
                    $udo_terms = get_terms(array(
                        'taxonomy' => 'unita_di_offerta',
                        'hide_empty' => false,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!is_wp_error($udo_terms) && !empty($udo_terms)) {
                        foreach ($udo_terms as $term) {
                            echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Filtro Profili Professionali -->
            <div class="meridiana-form-group">
                <label for="filter-profili" class="meridiana-form-label">
                    👤 Profilo Professionale
                </label>
                <select id="filter-profili" name="profili_professionali" class="meridiana-form-select">
                    <option value="">Tutti i profili</option>
                    <?php 
                    $profili_terms = get_terms(array(
                        'taxonomy' => 'profili_professionali',
                        'hide_empty' => false,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!is_wp_error($profili_terms) && !empty($profili_terms)) {
                        foreach ($profili_terms as $term) {
                            echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Filtro Aree di Competenza (solo per moduli) -->
            <div class="meridiana-form-group filter-aree meridiana-hidden">
                <label for="filter-aree" class="meridiana-form-label">
                    📋 Area di Competenza
                </label>
                <select id="filter-aree" name="aree_di_competenza" class="meridiana-form-select">
                    <option value="">Tutte le aree</option>
                    <?php 
                    $aree_terms = get_terms(array(
                        'taxonomy' => 'aree_di_competenza',
                        'hide_empty' => false,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!is_wp_error($aree_terms) && !empty($aree_terms)) {
                        foreach ($aree_terms as $term) {
                            echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                        }
                    }
                    ?>
                </select>
                <small class="meridiana-form-help">Disponibile solo per moduli</small>
            </div>

            <!-- Ordinamento -->
            <div class="meridiana-form-group">
                <label for="filter-sort" class="meridiana-form-label">
                    🔄 Ordina per
                </label>
                <select id="filter-sort" name="orderby" class="meridiana-form-select">
                    <option value="date">Data pubblicazione (più recenti)</option>
                    <option value="title">Titolo (A-Z)</option>
                    <option value="modified">Ultima modifica</option>
                    <option value="relevance">Rilevanza (se ricerca attiva)</option>
                </select>
            </div>

            <!-- Pulsanti azioni -->
            <div class="filter-actions meridiana-flex" style="gap: var(--meridiana-spacing-md); grid-column: 1 / -1; margin-top: var(--meridiana-spacing-lg); padding-top: var(--meridiana-spacing-xl); border-top: 1px solid var(--meridiana-border); justify-content: flex-end;">
                <button type="button" id="clear-filters" class="meridiana-btn meridiana-btn-secondary">
                    🗑️ Pulisci filtri
                </button>
                <button type="submit" class="meridiana-btn meridiana-btn-primary">
                    🔍 Cerca
                </button>
            </div>

        </form>
    </div>

    <!-- Risultati della ricerca -->
    <div class="documenti-results-container">
        
        <!-- Header risultati con counter e loader -->
        <div class="results-header meridiana-flex-between meridiana-mb-xl">
            <div class="results-info meridiana-flex" style="gap: var(--meridiana-spacing-lg);">
                <span id="results-count" class="results-count meridiana-font-semibold">
                    Caricamento documenti...
                </span>
                <div id="search-loader" class="meridiana-loader meridiana-hidden">
                    <div class="meridiana-spinner"></div>
                    <span>Ricerca in corso...</span>
                </div>
            </div>
            
            <!-- Vista griglia/lista toggle -->
            <div class="view-toggle meridiana-flex" style="border: 1px solid var(--meridiana-border); border-radius: var(--meridiana-radius-md); overflow: hidden;">
                <button type="button" id="view-grid" class="view-btn active" title="Vista griglia" style="padding: var(--meridiana-spacing-sm) var(--meridiana-spacing-md); background: var(--meridiana-primary); color: var(--meridiana-white); border: none; cursor: pointer; font-size: var(--meridiana-font-size-xs);">
                    ⚏ Griglia
                </button>
                <button type="button" id="view-list" class="view-btn" title="Vista lista" style="padding: var(--meridiana-spacing-sm) var(--meridiana-spacing-md); background: var(--meridiana-bg-light); color: var(--meridiana-text-light); border: none; cursor: pointer; font-size: var(--meridiana-font-size-xs);">
                    ☰ Lista
                </button>
            </div>
        </div>

        <!-- Container dei risultati -->
        <div id="documents-results" class="meridiana-grid-documents">
            <!-- I risultati vengono caricati qui via AJAX -->
        </div>

        <!-- Pagination -->
        <div id="documents-pagination" class="documents-pagination meridiana-flex-center" style="gap: var(--meridiana-spacing-md); margin-top: var(--meridiana-spacing-3xl); flex-wrap: wrap;">
            <!-- La pagination viene caricata qui via AJAX -->
        </div>

        <!-- Messaggio nessun risultato -->
        <div id="no-results-message" class="no-results meridiana-hidden">
            <div class="meridiana-card meridiana-text-center" style="padding: var(--meridiana-spacing-4xl) var(--meridiana-spacing-xl);">
                <h3 class="meridiana-text-primary meridiana-mb-lg" style="font-size: var(--meridiana-font-size-2xl);">🔍 Nessun documento trovato</h3>
                <p class="meridiana-mb-lg">Prova a:</p>
                <ul style="text-align: left; display: inline-block; margin: var(--meridiana-spacing-xl) 0;">
                    <li class="meridiana-text-light meridiana-mb-sm">Modificare i filtri di ricerca</li>
                    <li class="meridiana-text-light meridiana-mb-sm">Usare parole chiave diverse</li>
                    <li class="meridiana-text-light meridiana-mb-sm">Verificare l'ortografia</li>
                    <li class="meridiana-text-light meridiana-mb-sm">Selezionare più tipi di documento</li>
                </ul>
                <button type="button" onclick="clearAllFilters()" class="meridiana-btn meridiana-btn-secondary">
                    🗑️ Rimuovi tutti i filtri
                </button>
            </div>
        </div>

    </div>

</div>

<!-- JavaScript per AJAX e interattività -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Variabili globali
    let currentPage = 1;
    let isSearching = false;
    let searchTimeout = null;
    const debounceMs = <?php echo defined('MERIDIANA_SEARCH_DEBOUNCE_MS') ? MERIDIANA_SEARCH_DEBOUNCE_MS : 500; ?>;
    
    // Elementi del DOM
    const searchForm = document.getElementById('documenti-search-form');
    const searchInput = document.getElementById('search-text');
    const resultsContainer = document.getElementById('documents-results');
    const resultsCount = document.getElementById('results-count');
    const loader = document.getElementById('search-loader');
    const noResultsMessage = document.getElementById('no-results-message');
    const pagination = document.getElementById('documents-pagination');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const viewGridBtn = document.getElementById('view-grid');
    const viewListBtn = document.getElementById('view-list');
    
    // Toggle filtro aree di competenza basato su selezione moduli
    const contentTypeCheckboxes = document.querySelectorAll('input[name="content_types[]"]');
    const filtroAree = document.querySelector('.filter-aree');
    
    function toggleAreasFilter() {
        const moduliSelected = document.querySelector('input[name="content_types[]"][value="modulo"]').checked;
        filtroAree.classList.toggle('meridiana-hidden', !moduliSelected);
    }
    
    contentTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', toggleAreasFilter);
    });
    
    // Inizializza stato filtro aree
    toggleAreasFilter();
    
    // Ricerca in tempo reale
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch();
        }, debounceMs);
    });
    
    // Cambio filtri
    searchForm.addEventListener('change', function(e) {
        if (e.target.type !== 'text') {
            performSearch();
        }
    });
    
    // Submit form
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });
    
    // Clear filters
    clearFiltersBtn.addEventListener('click', function() {
        clearAllFilters();
    });
    
    // Toggle vista
    viewGridBtn.addEventListener('click', function() {
        switchView('grid');
    });
    
    viewListBtn.addEventListener('click', function() {
        switchView('list');
    });
    
    // Gestione parametri URL iniziali
    function loadInitialState() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchText = urlParams.get('search_text');
        
        if (searchText) {
            searchInput.value = searchText;
        }
    }
    
    // Funzione principale di ricerca
    function performSearch(page = 1) {
        if (isSearching) return;
        
        isSearching = true;
        currentPage = page;
        
        // Mostra loader
        loader.classList.remove('meridiana-hidden');
        resultsCount.textContent = 'Ricerca in corso...';
        noResultsMessage.classList.add('meridiana-hidden');
        resultsContainer.classList.add('meridiana-loading');
        
        // Raccogli dati form
        const formData = new FormData(searchForm);
        formData.append('action', 'meridiana_search_documents');
        formData.append('page', page);
        formData.append('nonce', '<?php echo wp_create_nonce("meridiana_search_nonce"); ?>');
        
        // AJAX request
        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.data);
            } else {
                displayError(data.data || 'Errore durante la ricerca');
            }
        })
        .catch(error => {
            console.error('Errore AJAX:', error);
            displayError('Errore di connessione. Riprova più tardi.');
        })
        .finally(() => {
            isSearching = false;
            loader.classList.add('meridiana-hidden');
            resultsContainer.classList.remove('meridiana-loading');
        });
    }
    
    // Visualizza risultati
    function displayResults(data) {
        resultsContainer.innerHTML = data.html;
        
        // Aggiorna contatore
        const countText = data.found_posts === 1 ? 
            '1 documento trovato' : 
            data.found_posts + ' documenti trovati';
        resultsCount.textContent = countText;
        
        // Gestisci pagination
        if (data.pagination) {
            pagination.innerHTML = data.pagination;
            setupPaginationEvents();
        } else {
            pagination.innerHTML = '';
        }
        
        // Mostra/nascondi messaggio no results
        if (data.found_posts === 0) {
            noResultsMessage.classList.remove('meridiana-hidden');
            resultsContainer.style.display = 'none';
        } else {
            noResultsMessage.classList.add('meridiana-hidden');
            resultsContainer.style.display = getCurrentView() === 'grid' ? 'grid' : 'flex';
        }
        
        // Setup eventi per le card
        setupCardEvents();
        
        // Log performance se in debug
        if (data.performance && window.console) {
            console.log('Search completed in ' + data.performance.duration_ms + 'ms');
        }
    }
    
    // Gestisci errori
    function displayError(message) {
        resultsContainer.innerHTML = '<div class="meridiana-alert meridiana-alert-error">⚠️ ' + message + '</div>';
        resultsCount.textContent = 'Errore durante la ricerca';
        noResultsMessage.classList.add('meridiana-hidden');
        pagination.innerHTML = '';
    }
    
    // Setup eventi pagination
    function setupPaginationEvents() {
        const paginationBtns = pagination.querySelectorAll('.pagination-btn');
        paginationBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page && page !== currentPage) {
                    performSearch(page);
                    
                    // Scroll to top dei risultati
                    resultsContainer.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }
            });
        });
    }
    
    // Setup eventi card documenti
    function setupCardEvents() {
        const documentCards = resultsContainer.querySelectorAll('.meridiana-document-card');
        documentCards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Se il click è su un link, non fare nulla
                if (e.target.tagName === 'A' || e.target.closest('a')) return;
                
                // Altrimenti, clicca sul link principale
                const link = card.querySelector('.meridiana-btn-view-doc');
                if (link) {
                    window.open(link.href, '_blank');
                }
            });
        });
    }
    
    // Pulisci tutti i filtri
    window.clearAllFilters = function() {
        searchForm.reset();
        
        // Reset checkboxes
        contentTypeCheckboxes.forEach(cb => cb.checked = true);
        
        // Reset select
        document.querySelectorAll('.meridiana-form-select').forEach(select => {
            select.selectedIndex = 0;
        });
        
        // Aggiorna filtro aree
        toggleAreasFilter();
        
        // Nuova ricerca
        performSearch();
    };
    
    // Ottieni vista corrente
    function getCurrentView() {
        return localStorage.getItem('meridiana_view_preference') || 'grid';
    }
    
    // Cambia vista
    function switchView(view) {
        if (view === 'grid') {
            viewGridBtn.classList.add('active');
            viewGridBtn.style.background = 'var(--meridiana-primary)';
            viewGridBtn.style.color = 'var(--meridiana-white)';
            viewListBtn.classList.remove('active');
            viewListBtn.style.background = 'var(--meridiana-bg-light)';
            viewListBtn.style.color = 'var(--meridiana-text-light)';
            resultsContainer.className = 'meridiana-grid-documents';
        } else {
            viewListBtn.classList.add('active');
            viewListBtn.style.background = 'var(--meridiana-primary)';
            viewListBtn.style.color = 'var(--meridiana-white)';
            viewGridBtn.classList.remove('active');
            viewGridBtn.style.background = 'var(--meridiana-bg-light)';
            viewGridBtn.style.color = 'var(--meridiana-text-light)';
            resultsContainer.className = 'meridiana-list-documents';
        }
        
        // Salva preferenza in localStorage
        localStorage.setItem('meridiana_view_preference', view);
    }
    
    // Ripristina preferenza vista
    const savedView = getCurrentView();
    switchView(savedView);
    
    // Carica stato iniziale da URL
    loadInitialState();
    
    // Carica risultati iniziali
    performSearch();
    
});
</script>

<?php get_footer(); ?>
