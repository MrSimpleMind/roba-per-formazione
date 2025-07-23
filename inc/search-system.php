<?php
/**
 * Modulo Sistema Ricerca Unificata - Child Theme La Meridiana
 * 
 * Gestisce la ricerca AJAX per protocolli, moduli e pianificazione ATS
 * Include estensioni alla ricerca WordPress per campi custom
 * 
 * @version 1.0.0
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/* ========================================
   HANDLER AJAX PRINCIPALE
   ======================================== */

/**
 * Handler AJAX per la ricerca documenti unificata
 * Gestisce protocolli, moduli e pianificazione ATS
 */
add_action('wp_ajax_meridiana_search_documents', 'meridiana_handle_search_documents');
add_action('wp_ajax_nopriv_meridiana_search_documents', 'meridiana_handle_search_documents');

function meridiana_handle_search_documents() {
    // Verifica nonce per sicurezza
    if (!wp_verify_nonce($_POST['nonce'], 'meridiana_search_nonce')) {
        wp_die(json_encode(array(
            'success' => false,
            'data' => 'Errore di sicurezza'
        )));
    }
    
    $start_time = microtime(true);
    
    // Sanitizza input
    $search_text = sanitize_text_field($_POST['search_text'] ?? '');
    $content_types = array_map('sanitize_text_field', $_POST['content_types'] ?? array('protocollo', 'modulo'));
    $unita_offerta = sanitize_text_field($_POST['unita_di_offerta'] ?? '');
    $profili_professionali = sanitize_text_field($_POST['profili_professionali'] ?? '');
    $aree_competenza = sanitize_text_field($_POST['aree_di_competenza'] ?? '');
    $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
    $page = max(1, intval($_POST['page'] ?? 1));
    
    // Costruisci query
    $query_args = meridiana_build_documents_query(
        $search_text,
        $content_types,
        $unita_offerta,
        $profili_professionali,
        $aree_competenza,
        $orderby,
        $page
    );
    
    // Esegui query
    $query = new WP_Query($query_args);
    
    // Genera HTML risultati
    $html = meridiana_generate_results_html($query, $search_text);
    
    // Genera pagination
    $pagination = meridiana_generate_pagination_html($query, $page);
    
    // Performance monitoring
    $duration = meridiana_search_performance_monitor($start_time);
    
    // Tracking analytics (se abilitato)
    if (defined('MERIDIANA_ENABLE_ANALYTICS') && MERIDIANA_ENABLE_ANALYTICS) {
        meridiana_track_search_analytics($search_text, $query->found_posts, array(
            'content_types' => $content_types,
            'unita_offerta' => $unita_offerta,
            'profili_professionali' => $profili_professionali,
            'aree_competenza' => $aree_competenza,
            'duration_ms' => $duration
        ));
    }
    
    // Risposta JSON
    wp_send_json_success(array(
        'html' => $html,
        'found_posts' => $query->found_posts,
        'pagination' => $pagination,
        'current_page' => $page,
        'max_pages' => $query->max_num_pages,
        'search_term' => $search_text,
        'performance' => array(
            'duration_ms' => $duration,
            'query_count' => get_num_queries()
        )
    ));
}

/* ========================================
   QUERY BUILDER
   ======================================== */

/**
 * Costruisce gli argomenti per WP_Query basati sui filtri
 */
function meridiana_build_documents_query($search_text, $content_types, $unita_offerta, $profili_professionali, $aree_competenza, $orderby, $page) {
    
    $posts_per_page = defined('MERIDIANA_SEARCH_POSTS_PER_PAGE') ? MERIDIANA_SEARCH_POSTS_PER_PAGE : 12;
    
    // Post types da cercare
    $post_types = array();
    
    if (in_array('protocollo', $content_types) || in_array('pianificazione', $content_types)) {
        $post_types[] = 'protocollo';
    }
    
    if (in_array('modulo', $content_types)) {
        $post_types[] = 'modulo';
    }
    
    // Se nessun post type selezionato, usa tutti
    if (empty($post_types)) {
        $post_types = array('protocollo', 'modulo');
    }
    
    // Argomenti base query
    $args = array(
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'meta_query' => array(),
        'tax_query' => array(),
        'suppress_filters' => false // Importante per estensioni ricerca
    );
    
    // Ricerca testuale
    if (!empty($search_text)) {
        $args['s'] = $search_text;
        
        // Se c'è ricerca testuale, cerca anche nei campi custom
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key' => 'riassunto',
                'value' => $search_text,
                'compare' => 'LIKE'
            )
        );
        
        // Modifica priorità ordinamento se c'è ricerca
        if ($orderby === 'relevance') {
            $args['orderby'] = 'relevance';
            $args['order'] = 'DESC';
        }
    }
    
    // Filtro pianificazione ATS (solo per protocolli)
    if (in_array('pianificazione', $content_types) && !in_array('protocollo', $content_types)) {
        // Solo pianificazione ATS
        $args['meta_query'][] = array(
            'key' => 'pianificazione_ats',
            'value' => '1',
            'compare' => '='
        );
    } elseif (!in_array('pianificazione', $content_types) && in_array('protocollo', $content_types)) {
        // Solo protocolli normali (esclude pianificazione)
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key' => 'pianificazione_ats',
                'value' => '1',
                'compare' => '!='
            ),
            array(
                'key' => 'pianificazione_ats',
                'compare' => 'NOT EXISTS'
            )
        );
    }
    
    // Taxonomies filters
    if (!empty($unita_offerta)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'unita_di_offerta',
            'field' => 'slug',
            'terms' => $unita_offerta
        );
    }
    
    if (!empty($profili_professionali)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'profili_professionali',
            'field' => 'slug',
            'terms' => $profili_professionali
        );
    }
    
    if (!empty($aree_competenza)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'aree_di_competenza',
            'field' => 'slug',
            'terms' => $aree_competenza
        );
    }
    
    // Relazione tax_query
    if (count($args['tax_query']) > 1) {
        $args['tax_query']['relation'] = 'AND';
    }
    
    // Relazione meta_query
    if (count($args['meta_query']) > 1) {
        $args['meta_query']['relation'] = 'AND';
    }
    
    // Ordinamento
    switch ($orderby) {
        case 'title':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        case 'modified':
            $args['orderby'] = 'modified';
            $args['order'] = 'DESC';
            break;
        case 'date':
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }
    
    return $args;
}

/* ========================================
   GENERAZIONE HTML RISULTATI
   ======================================== */

/**
 * Genera HTML per i risultati della ricerca
 */
function meridiana_generate_results_html($query, $search_term = '') {
    if (!$query->have_posts()) {
        return '';
    }
    
    $html = '';
    
    while ($query->have_posts()) {
        $query->the_post();
        
        $post_type = get_post_type();
        $post_id = get_the_ID();
        
        // Determina il tipo di documento per il badge
        $doc_type = meridiana_get_document_type($post_type, $post_id);
        $badge_class = 'meridiana-badge-' . $doc_type['slug'];
        
        // Ottieni excerpt personalizzato
        $excerpt = meridiana_get_document_excerpt($post_id, $post_type);
        
        // Evidenzia termini di ricerca se presenti
        $title = get_the_title();
        if (!empty($search_term) && function_exists('meridiana_highlight_search_terms')) {
            $title = meridiana_highlight_search_terms($title, $search_term);
            $excerpt = meridiana_highlight_search_terms($excerpt, $search_term);
        }
        
        // Ottieni meta informazioni
        $meta_tags = meridiana_get_document_meta_tags($post_id, $post_type);
        
        // URL del documento
        $doc_url = get_permalink($post_id);
        
        // Data formattata
        $date = meridiana_format_document_date(get_the_date('Y-m-d H:i:s'));
        
        $html .= '<div class="meridiana-document-card" data-post-id="' . $post_id . '">';
        
        // Badge tipo documento
        $html .= '<div class="meridiana-document-type-badge ' . $badge_class . '">';
        $html .= esc_html($doc_type['label']);
        $html .= '</div>';
        
        // Titolo (con evidenziazione)
        $html .= '<h3 class="meridiana-document-title">' . $title . '</h3>';
        
        // Excerpt (con evidenziazione)
        if (!empty($excerpt)) {
            $html .= '<div class="meridiana-document-excerpt">' . $excerpt . '</div>';
        }
        
        // Meta tags
        if (!empty($meta_tags)) {
            $html .= '<div class="meridiana-document-meta">';
            foreach ($meta_tags as $tag) {
                $html .= '<span class="meridiana-meta-tag">' . esc_html($tag) . '</span>';
            }
            $html .= '</div>';
        }
        
        // Azioni
        $html .= '<div class="meridiana-document-actions">';
        $html .= '<a href="' . esc_url($doc_url) . '" class="meridiana-btn-view-doc" target="_blank">';
        $html .= '👁️ Visualizza';
        $html .= '</a>';
        $html .= '<span class="meridiana-document-date">' . $date . '</span>';
        $html .= '</div>';
        
        $html .= '</div>';
    }
    
    wp_reset_postdata();
    
    return $html;
}

/* ========================================
   PAGINATION
   ======================================== */

/**
 * Genera HTML per la pagination
 */
function meridiana_generate_pagination_html($query, $current_page) {
    
    if ($query->max_num_pages <= 1) {
        return '';
    }
    
    $html = '';
    $max_pages = $query->max_num_pages;
    
    // Pulsante precedente
    if ($current_page > 1) {
        $html .= '<button type="button" class="pagination-btn meridiana-btn meridiana-btn-secondary" data-page="' . ($current_page - 1) . '">';
        $html .= '‹ Precedente';
        $html .= '</button>';
    }
    
    // Range di pagine da mostrare
    $start_page = max(1, $current_page - 2);
    $end_page = min($max_pages, $current_page + 2);
    
    // Prima pagina se non inclusa nel range
    if ($start_page > 1) {
        $html .= '<button type="button" class="pagination-btn meridiana-btn meridiana-btn-secondary" data-page="1">1</button>';
        if ($start_page > 2) {
            $html .= '<span class="pagination-dots meridiana-text-light">...</span>';
        }
    }
    
    // Pagine nel range
    for ($i = $start_page; $i <= $end_page; $i++) {
        $class = ($i === $current_page) ? 'pagination-btn meridiana-btn meridiana-btn-primary current' : 'pagination-btn meridiana-btn meridiana-btn-secondary';
        $html .= '<button type="button" class="' . $class . '" data-page="' . $i . '">';
        $html .= $i;
        $html .= '</button>';
    }
    
    // Ultima pagina se non inclusa nel range
    if ($end_page < $max_pages) {
        if ($end_page < $max_pages - 1) {
            $html .= '<span class="pagination-dots meridiana-text-light">...</span>';
        }
        $html .= '<button type="button" class="pagination-btn meridiana-btn meridiana-btn-secondary" data-page="' . $max_pages . '">' . $max_pages . '</button>';
    }
    
    // Pulsante successivo
    if ($current_page < $max_pages) {
        $html .= '<button type="button" class="pagination-btn meridiana-btn meridiana-btn-secondary" data-page="' . ($current_page + 1) . '">';
        $html .= 'Successivo ›';
        $html .= '</button>';
    }
    
    return $html;
}

/* ========================================
   ESTENSIONI RICERCA WORDPRESS
   ======================================== */

/**
 * Estende la ricerca WordPress per includere campi custom
 * Questo migliora la ricerca testuale anche nei riassunti dei protocolli
 */
function meridiana_extend_search_to_meta_fields($join, $query) {
    global $wpdb;
    
    // Solo per ricerche frontend e per i nostri CPT
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $post_types = $query->get('post_type');
        
        if (empty($post_types)) {
            $post_types = array('post', 'page', 'protocollo', 'modulo');
        }
        
        // Se stiamo cercando nei nostri CPT, includi meta fields
        if (is_array($post_types) && (in_array('protocollo', $post_types) || in_array('modulo', $post_types))) {
            $join .= " LEFT JOIN {$wpdb->postmeta} pm ON {$wpdb->posts}.ID = pm.post_id";
        }
    }
    
    return $join;
}
add_filter('posts_join', 'meridiana_extend_search_to_meta_fields', 10, 2);

/**
 * Modifica la clausola WHERE per includere meta fields nella ricerca
 */
function meridiana_extend_search_where($where, $query) {
    global $wpdb;
    
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $search_term = $query->get('s');
        
        if (!empty($search_term)) {
            $post_types = $query->get('post_type');
            
            if (empty($post_types)) {
                $post_types = array('post', 'page', 'protocollo', 'modulo');
            }
            
            if (is_array($post_types) && (in_array('protocollo', $post_types) || in_array('modulo', $post_types))) {
                // Aggiungi ricerca nei meta fields
                $where .= " OR (pm.meta_key = 'riassunto' AND pm.meta_value LIKE '%" . esc_sql($search_term) . "%')";
            }
        }
    }
    
    return $where;
}
add_filter('posts_where', 'meridiana_extend_search_where', 10, 2);

/**
 * Previeni duplicati nei risultati di ricerca quando si cercano meta fields
 */
function meridiana_prevent_search_duplicates($groupby, $query) {
    global $wpdb;
    
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $post_types = $query->get('post_type');
        
        if (is_array($post_types) && (in_array('protocollo', $post_types) || in_array('modulo', $post_types))) {
            $groupby = "{$wpdb->posts}.ID";
        }
    }
    
    return $groupby;
}
add_filter('posts_groupby', 'meridiana_prevent_search_duplicates', 10, 2);

/* ========================================
   ANALYTICS E PERFORMANCE
   ======================================== */

/**
 * Traccia le ricerche per analytics future
 */
function meridiana_track_search_analytics($search_term, $results_count, $filters_used) {
    // Tracking solo se abilitato
    if (!defined('MERIDIANA_ENABLE_TRACKING') || !MERIDIANA_ENABLE_TRACKING) {
        return;
    }
    
    // Log per debug
    if (WP_DEBUG && function_exists('meridiana_log')) {
        meridiana_log("Search: '{$search_term}' - {$results_count} results", json_encode($filters_used));
    }
    
    // Future: salva in database per report admin
    do_action('meridiana_search_performed', $search_term, $results_count, $filters_used);
}

/**
 * Hook per performance monitoring
 */
function meridiana_search_performance_monitor($start_time) {
    $end_time = microtime(true);
    $duration = round(($end_time - $start_time) * 1000, 2); // millisecondi
    
    if (WP_DEBUG && $duration > 1000 && function_exists('meridiana_log')) {
        meridiana_log("Slow search query detected: {$duration}ms");
    }
    
    return $duration;
}

/* ========================================
   CSS AGGIUNTIVO PER RICERCA
   ======================================== */

/**
 * Aggiunge CSS per highlighting e miglioramenti ricerca
 */
function meridiana_add_search_styles() {
    ?>
    <style>
    /* Highlighting termini di ricerca */
    .search-highlight {
        background: #FFE082;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
        color: #1C1C1C;
    }
    
    /* Stili per errori di ricerca */
    .search-error {
        background: #FFEBEE;
        color: #C62828;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        border: 1px solid #FFCDD2;
        margin: 20px 0;
    }
    
    /* Dots pagination */
    .pagination-dots {
        padding: 10px 5px;
        color: #5A5A5A;
        font-size: 14px;
        align-self: center;
    }
    
    /* Loading states */
    .documents-grid.loading {
        opacity: 0.6;
        pointer-events: none;
        position: relative;
    }
    
    .documents-grid.loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 40px;
        height: 40px;
        border: 3px solid #EAEAEA;
        border-top: 3px solid #AB1120;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        transform: translate(-50%, -50%);
        z-index: 1000;
    }
    
    /* Hover effects migliorati */
    .document-card:hover .document-title {
        color: #AB1120;
        transition: color 0.2s;
    }
    
    .document-card:hover .btn-view-doc {
        transform: translateX(2px);
        transition: transform 0.2s;
    }
    </style>
    <?php
}
add_action('wp_head', 'meridiana_add_search_styles');

/**
 * Fine modulo sistema ricerca
 */