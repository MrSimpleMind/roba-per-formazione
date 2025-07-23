<?php
/**
 * Modulo Document Helpers - Child Theme La Meridiana
 * 
 * Contiene tutte le funzioni helper per gestire documenti:
 * - Determinazione tipo documento
 * - Estrazione excerpt e meta
 * - Tracking visualizzazioni
 * - Gestione permessi download
 * 
 * @version 1.0.0
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/* ========================================
   DETERMINAZIONE TIPO DOCUMENTO
   ======================================== */

/**
 * Determina il tipo di documento per badge e categorizzazione
 */
function meridiana_get_document_type($post_type, $post_id) {
    
    if ($post_type === 'modulo') {
        return array(
            'slug' => 'modulo',
            'label' => '📋 Modulo',
            'color' => '#388E3C',
            'bg_color' => '#E8F5E8'
        );
    }
    
    if ($post_type === 'protocollo') {
        $is_pianificazione = get_field('pianificazione_ats', $post_id);
        
        if ($is_pianificazione) {
            return array(
                'slug' => 'pianificazione',
                'label' => '⚠️ Pianificazione ATS',
                'color' => '#F57C00',
                'bg_color' => '#FFF3E0'
            );
        } else {
            return array(
                'slug' => 'protocollo',
                'label' => '📄 Protocollo',
                'color' => '#1976D2',
                'bg_color' => '#E8F4FD'
            );
        }
    }
    
    // Fallback per altri tipi
    return array(
        'slug' => 'documento',
        'label' => '📄 Documento',
        'color' => '#5A5A5A',
        'bg_color' => '#F9FAFC'
    );
}

/* ========================================
   GESTIONE EXCERPT E CONTENUTO
   ======================================== */

/**
 * Ottieni excerpt personalizzato per documento
 */
function meridiana_get_document_excerpt($post_id, $post_type, $word_limit = 25) {
    
    // Prima prova con il riassunto se è un protocollo
    if ($post_type === 'protocollo') {
        $riassunto = get_field('riassunto', $post_id);
        if (!empty($riassunto)) {
            return wp_trim_words(strip_tags($riassunto), $word_limit, '...');
        }
    }
    
    // Fallback su excerpt WordPress
    $excerpt = get_the_excerpt($post_id);
    if (!empty($excerpt)) {
        return wp_trim_words($excerpt, $word_limit, '...');
    }
    
    // Ultimo fallback su contenuto
    $content = get_post_field('post_content', $post_id);
    if (!empty($content)) {
        return wp_trim_words(strip_tags($content), $word_limit, '...');
    }
    
    return '';
}

/**
 * Funzione per evidenziare i termini di ricerca nei risultati
 */
function meridiana_highlight_search_terms($text, $search_term) {
    if (empty($search_term) || empty($text)) {
        return $text;
    }
    
    // Escape del termine di ricerca per regex sicura
    $escaped_term = preg_quote($search_term, '/');
    
    // Evidenzia solo se il termine ha almeno 3 caratteri
    if (strlen($search_term) >= 3) {
        $highlighted = preg_replace(
            '/(' . $escaped_term . ')/i',
            '<mark class="search-highlight">$1</mark>',
            $text
        );
        
        return $highlighted;
    }
    
    return $text;
}

/* ========================================
   META TAGS E TAXONOMIES
   ======================================== */

/**
 * Ottieni meta tags per il documento
 */
function meridiana_get_document_meta_tags($post_id, $post_type, $max_tags = 4) {
    $tags = array();
    
    // UDO
    $udo_terms = get_the_terms($post_id, 'unita_di_offerta');
    if ($udo_terms && !is_wp_error($udo_terms)) {
        $udo_count = count($udo_terms);
        foreach (array_slice($udo_terms, 0, 2) as $term) {
            $tags[] = '🏢 ' . $term->name;
        }
        if ($udo_count > 2) {
            $tags[] = '+' . ($udo_count - 2) . ' UDO';
        }
    }
    
    // Se abbiamo già troppi tag, fermiamoci qui
    if (count($tags) >= $max_tags) {
        return array_slice($tags, 0, $max_tags);
    }
    
    // Profili professionali
    $profili_terms = get_the_terms($post_id, 'profili_professionali');
    if ($profili_terms && !is_wp_error($profili_terms)) {
        $profili_count = count($profili_terms);
        $remaining_slots = $max_tags - count($tags);
        
        foreach (array_slice($profili_terms, 0, min(1, $remaining_slots)) as $term) {
            $tags[] = '👤 ' . $term->name;
        }
        
        if ($profili_count > 1 && $remaining_slots > 1) {
            $tags[] = '+' . ($profili_count - 1) . ' profili';
        }
    }
    
    // Aree di competenza (solo per moduli e se c'è ancora spazio)
    if ($post_type === 'modulo' && count($tags) < $max_tags) {
        $aree_terms = get_the_terms($post_id, 'aree_di_competenza');
        if ($aree_terms && !is_wp_error($aree_terms)) {
            foreach (array_slice($aree_terms, 0, 1) as $term) {
                $tags[] = '📋 ' . $term->name;
            }
        }
    }
    
    return array_slice($tags, 0, $max_tags);
}

/**
 * Ottieni tutti i termini di una taxonomy per un documento (per filtri)
 */
function meridiana_get_document_taxonomy_terms($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    
    if ($terms && !is_wp_error($terms)) {
        return wp_list_pluck($terms, 'name');
    }
    
    return array();
}

/* ========================================
   CONTEGGI E STATISTICHE
   ======================================== */

/**
 * Ottieni conteggio documenti per tipo (per stats)
 */
function meridiana_get_document_counts() {
    // Cache per 5 minuti per performance
    $cache_key = 'meridiana_document_counts';
    $counts = wp_cache_get($cache_key);
    
    if (false === $counts) {
        $counts = array(
            'protocolli' => 0,
            'moduli' => 0,
            'pianificazione' => 0,
            'totale' => 0
        );
        
        // Conta protocolli
        $protocolli = wp_count_posts('protocollo');
        $counts['protocolli'] = isset($protocolli->publish) ? $protocolli->publish : 0;
        
        // Conta moduli
        $moduli = wp_count_posts('modulo');
        $counts['moduli'] = isset($moduli->publish) ? $moduli->publish : 0;
        
        // Conta pianificazione ATS (protocolli con flag)
        $pianificazione_query = new WP_Query(array(
            'post_type' => 'protocollo',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'pianificazione_ats',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $counts['pianificazione'] = $pianificazione_query->found_posts;
        wp_reset_postdata();
        
        // Totale
        $counts['totale'] = $counts['protocolli'] + $counts['moduli'];
        
        // Cache per 5 minuti
        wp_cache_set($cache_key, $counts, '', 300);
    }
    
    return $counts;
}

/**
 * Ottieni documenti recenti per dashboard
 */
function meridiana_get_recent_documents($limit = 5, $days = 30) {
    $cache_key = 'meridiana_recent_docs_' . $limit . '_' . $days;
    $recent_docs = wp_cache_get($cache_key);
    
    if (false === $recent_docs) {
        $recent_docs = get_posts(array(
            'post_type' => array('protocollo', 'modulo'),
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => $days . ' days ago'
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_wp_attachment_metadata', // Escludi attachment
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        // Cache per 10 minuti
        wp_cache_set($cache_key, $recent_docs, '', 600);
    }
    
    return $recent_docs;
}

/* ========================================
   TRACKING E ANALYTICS
   ======================================== */

/**
 * Funzione per tracking visualizzazioni documenti (preparatoria)
 * Sarà espansa quando implementeremo il sistema analytics completo
 */
function meridiana_track_document_view($post_id, $user_id = null, $context = 'view') {
    
    // Verifica che il tracking sia abilitato
    if (!defined('MERIDIANA_ENABLE_TRACKING') || !MERIDIANA_ENABLE_TRACKING) {
        return false;
    }
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Se non c'è utente loggato, non tracciare
    if (!$user_id) {
        return false;
    }
    
    // Log per debug
    if (WP_DEBUG && function_exists('meridiana_log')) {
        $post_title = get_the_title($post_id);
        $user = get_userdata($user_id);
        $username = $user ? $user->user_login : 'unknown';
        
        meridiana_log("Document view tracked", array(
            'post_id' => $post_id,
            'post_title' => $post_title,
            'user_id' => $user_id,
            'username' => $username,
            'context' => $context,
            'timestamp' => current_time('mysql')
        ));
    }
    
    // Hook per estensioni future
    do_action('meridiana_document_viewed', $post_id, $user_id, $context);
    
    return true;
}

/**
 * Ottieni statistiche visualizzazioni per un documento (preparatoria)
 */
function meridiana_get_document_view_stats($post_id) {
    // Placeholder per future implementazioni
    // Quando avremo la tabella analytics, ritornerà dati reali
    
    return array(
        'total_views' => 0,
        'unique_viewers' => 0,
        'last_viewed' => null,
        'most_recent_viewers' => array()
    );
}

/* ========================================
   GESTIONE PERMESSI E SICUREZZA
   ======================================== */

/**
 * Verifica se un utente può visualizzare un documento
 */
function meridiana_can_user_view_document($post_id, $user_id = null) {
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Se non c'è utente, nega accesso
    if (!$user_id) {
        return false;
    }
    
    // Admin può sempre visualizzare
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Il post deve essere pubblicato
    if (get_post_status($post_id) !== 'publish') {
        return false;
    }
    
    // Dipendenti possono visualizzare documenti pubblicati
    if (user_can($user_id, 'read')) {
        return true;
    }
    
    return false;
}

/**
 * Verifica se un utente può scaricare un documento
 */
function meridiana_can_user_download($post_type, $post_id = null, $user_id = null) {
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Se non può visualizzare, non può scaricare
    if ($post_id && !meridiana_can_user_view_document($post_id, $user_id)) {
        return false;
    }
    
    // Admin può sempre scaricare
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Logica specifica per tipo documento
    switch ($post_type) {
        case 'protocollo':
            // Protocolli: solo visualizzazione (no download)
            return false;
            
        case 'modulo':
            // Moduli: download permesso per dipendenti
            return user_can($user_id, 'read');
            
        case 'convenzione':
        case 'contatto':
        case 'salute':
            // Altri tipi: download permesso
            return user_can($user_id, 'read');
            
        default:
            return false;
    }
}

/* ========================================
   UTILITY FUNCTIONS
   ======================================== */

/**
 * Formatta data in modo user-friendly
 */
function meridiana_format_document_date($date, $format = 'd/m/Y') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = is_string($date) ? strtotime($date) : $date;
    
    // Se la data è oggi
    if (date('Y-m-d', $timestamp) === date('Y-m-d')) {
        return 'Oggi, ' . date('H:i', $timestamp);
    }
    
    // Se la data è ieri
    if (date('Y-m-d', $timestamp) === date('Y-m-d', strtotime('-1 day'))) {
        return 'Ieri, ' . date('H:i', $timestamp);
    }
    
    // Se la data è questa settimana
    if ($timestamp > strtotime('-7 days')) {
        $days = array(
            'Monday' => 'Lunedì',
            'Tuesday' => 'Martedì',
            'Wednesday' => 'Mercoledì',
            'Thursday' => 'Giovedì',
            'Friday' => 'Venerdì',
            'Saturday' => 'Sabato',
            'Sunday' => 'Domenica'
        );
        
        $day_name = $days[date('l', $timestamp)] ?? date('l', $timestamp);
        return $day_name . ', ' . date('H:i', $timestamp);
    }
    
    // Altrimenti usa il formato standard
    return date($format, $timestamp);
}

/**
 * Ottieni icona per tipo documento
 */
function meridiana_get_document_icon($post_type, $post_id = null) {
    $doc_type = meridiana_get_document_type($post_type, $post_id);
    
    switch ($doc_type['slug']) {
        case 'protocollo':
            return '📄';
        case 'modulo':
            return '📋';
        case 'pianificazione':
            return '⚠️';
        case 'convenzione':
            return '🤝';
        case 'contatto':
            return '👤';
        case 'salute':
            return '🏥';
        default:
            return '📄';
    }
}

/**
 * Cache cleanup quando un documento viene aggiornato
 */
function meridiana_clear_document_cache($post_id) {
    $post_type = get_post_type($post_id);
    
    // Pulisci solo se è uno dei nostri CPT
    if (in_array($post_type, array('protocollo', 'modulo', 'convenzione', 'contatto', 'salute'))) {
        wp_cache_delete('meridiana_document_counts');
        wp_cache_delete('meridiana_recent_docs_5_30');
        wp_cache_delete('meridiana_recent_docs_10_30');
        
        // Log se in debug
        if (WP_DEBUG && function_exists('meridiana_log')) {
            meridiana_log('Document cache cleared for post: ' . $post_id);
        }
    }
}
add_action('save_post', 'meridiana_clear_document_cache');
add_action('delete_post', 'meridiana_clear_document_cache');

/**
 * Fine modulo document helpers
 */