<?php
/**
 * Template Name: Ricerca Protocolli
 * Description: Pagina con ricerca e filtro in tempo reale dei CPT Protocolli.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue CSS and JS only for this template
wp_enqueue_style('protocolli-search', get_stylesheet_directory_uri() . '/css/protocolli-search.css', array(), '1.0');
wp_enqueue_script('protocolli-search', get_stylesheet_directory_uri() . '/js/protocolli-search.js', array(), '1.0', true);
wp_localize_script('protocolli-search', 'protocolliSearch', array(
    'apiURL' => esc_url_raw(rest_url('wp/v2'))
));

get_header();
?>

<div id="protocolli-search-app" class="protocollo-search-container">
    <div class="protocollo-search-controls">
        <input type="text" id="protocollo-search-input" placeholder="Cerca protocollo..." />
        <select id="protocollo-filter-unita">
            <option value="">Tutte le Unità di Offerta</option>
        </select>
        <select id="protocollo-filter-profilo">
            <option value="">Tutti i Profili Professionali</option>
        </select>
    </div>
    <div id="protocollo-results" class="protocollo-results">
        <p>Caricamento...</p>
    </div>
</div>

<?php
get_footer();
