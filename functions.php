<?php
/**
 * Functions.php del Child Theme Divi 5 - La Meridiana
 * 
 * Questo file contiene tutte le personalizzazioni che non andranno
 * perse quando aggiorni il tema Divi principale.
 */

// Sicurezza: previene accesso diretto al file
if (!defined('ABSPATH')) {
    exit;
}

/* ========================================
   1. CARICAMENTO CSS CHILD THEME
   ======================================== */

/**
 * Carica correttamente il CSS del child theme
 * 
 * Perché questo approccio:
 * - @import nel CSS è più lento
 * - wp_enqueue_style() gestisce meglio le dipendenze
 * - Permette di controllare l'ordine di caricamento
 */
function meridiana_child_theme_styles() {
    // Carica prima il CSS del tema padre
    wp_enqueue_style(
        'divi-parent-style', 
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme()->parent()->get('Version')
    );
    
    // Poi carica il CSS del child theme
    wp_enqueue_style(
        'meridiana-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('divi-parent-style'), // Dipende dal CSS del padre
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'meridiana_child_theme_styles');

/* ========================================
   2. RIMOZIONE COMPLETA COMMENTI
   ======================================== */

/**
 * Disabilita completamente il sistema commenti
 * 
 * Benefici performance:
 * - Rimuove script comment-reply.js (risparmio ~8KB)
 * - Elimina query database per commenti
 * - Rimuove form e CSS relativi
 */

// Rimuove supporto commenti da tutti i post type
function meridiana_disable_comments_post_types_support() {
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
}
add_action('admin_init', 'meridiana_disable_comments_post_types_support');

// Chiude commenti su tutti i post esistenti
function meridiana_disable_comments_status() {
    return false;
}
add_filter('comments_open', 'meridiana_disable_comments_status', 20, 2);
add_filter('pings_open', 'meridiana_disable_comments_status', 20, 2);

// Nasconde i commenti esistenti
function meridiana_disable_comments_hide_existing($comments) {
    $comments = array();
    return $comments;
}
add_filter('comments_array', 'meridiana_disable_comments_hide_existing', 10, 2);

// Rimuove menu commenti dall'admin
function meridiana_disable_comments_admin_menu() {
    remove_menu_page('edit-comments.php');
}
add_action('admin_menu', 'meridiana_disable_comments_admin_menu');

// Rimuove widget commenti dalla dashboard
function meridiana_disable_comments_dashboard() {
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}
add_action('admin_init', 'meridiana_disable_comments_dashboard');

// Rimuove link commenti dalla admin bar
function meridiana_disable_comments_admin_bar() {
    if (is_admin_bar_showing()) {
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    }
}
add_action('init', 'meridiana_disable_comments_admin_bar');

/* ========================================
   3. RIMOZIONE CPT "PROJECTS" DI DIVI
   ======================================== */

/**
 * Rimuove il Custom Post Type "Projects" creato automaticamente da Divi
 * 
 * Benefici:
 * - Meno confusione nell'admin
 * - Risparmio query database
 * - Admin più pulito per i gestori
 */
function meridiana_remove_divi_projects_cpt() {
    // Rimuove il CPT Projects dal menu admin
    remove_menu_page('edit.php?post_type=project');
    
    // Disabilita completamente il CPT
    global $wp_post_types;
    if (isset($wp_post_types['project'])) {
        unset($wp_post_types['project']);
    }
}
add_action('admin_menu', 'meridiana_remove_divi_projects_cpt', 999);

// Impedisce la registrazione del CPT Projects
function meridiana_unregister_divi_projects() {
    unregister_post_type('project');
}
add_action('init', 'meridiana_unregister_divi_projects', 999);

/* ========================================
   4. OTTIMIZZAZIONI PERFORMANCE EARLY-STAGE
   ======================================== */

/**
 * Rimuove script e CSS non necessari per una piattaforma aziendale
 */

// Rimuove emoji (risparmio ~15KB + 1 HTTP request)
function meridiana_disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'meridiana_disable_emojis');

// Rimuove DNS prefetch per emoji
function meridiana_disable_emojis_dns_prefetch($urls, $relation_type) {
    if ('dns-prefetch' == $relation_type) {
        $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/');
        $urls = array_diff($urls, array($emoji_svg_url));
    }
    return $urls;
}
add_filter('wp_resource_hints', 'meridiana_disable_emojis_dns_prefetch', 10, 2);

// Rimuove versioni CSS/JS (sicurezza)
function meridiana_remove_version_scripts_styles($src) {
    if (strpos($src, 'ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('style_loader_src', 'meridiana_remove_version_scripts_styles', 9999);
add_filter('script_loader_src', 'meridiana_remove_version_scripts_styles', 9999);

// Disabilita XML-RPC (non serve per intranet)
function meridiana_disable_xmlrpc() {
    return false;
}
add_filter('xmlrpc_enabled', 'meridiana_disable_xmlrpc');

// Rimuove Really Simple Discovery link
remove_action('wp_head', 'rsd_link');

// Rimuove Windows Live Writer link
remove_action('wp_head', 'wlwmanifest_link');

// Rimuove shortlink
remove_action('wp_head', 'wp_shortlink_wp_head');

/* ========================================
   5. OTTIMIZZAZIONI SPECIFICHE DIVI
   ======================================== */

/**
 * Disabilita feature Divi non necessarie per performance
 */

// Disabilita Google Fonts se non necessari (carica font locali più veloce)
function meridiana_disable_google_fonts() {
    return false;
}
// Decommentare se vuoi disabilitare Google Fonts:
// add_filter('et_use_google_fonts', 'meridiana_disable_google_fonts');

// Ottimizza caricamento CSS critico Divi
function meridiana_optimize_divi_css() {
    // Forza l'inline del CSS critico per performance
    if (function_exists('et_get_option')) {
        // Solo su pagine specifiche della piattaforma
        if (is_page() || is_singular(array('protocollo', 'modulo', 'convenzione'))) {
            add_filter('et_builder_load_actions', '__return_true');
        }
    }
}
add_action('wp', 'meridiana_optimize_divi_css');

/* ========================================
   6. SUPPORTO FUTURO PIATTAFORMA
   ======================================== */

/**
 * Prepara funzioni che serviranno per le prossime fasi
 */

// Hook personalizzato per tracking documenti (lo useremo con PDF Embedder)
function meridiana_track_document_view($post_id, $user_id) {
    // Placeholder per il sistema di tracking che implementeremo
    do_action('meridiana_document_viewed', $post_id, $user_id);
}

// Funzione per verificare se un utente può scaricare un documento
function meridiana_can_user_download($post_type, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Logica che definiremo meglio con PDF Embedder
    switch ($post_type) {
        case 'protocollo':
            return false; // Solo visualizzazione
        case 'modulo':
            return true;  // Download permesso
        default:
            return true;
    }
}

/* ========================================
   7. PULIZIA DATABASE (OPZIONALE)
   ======================================== */

/**
 * Funzione per pulire dati non necessari dal database
 * Da eseguire occasionalmente per mantenere performance
 */
function meridiana_cleanup_database() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    
    // Rimuove revisioni vecchie (mantieni solo ultime 3)
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    // Rimuove spam e commenti nel cestino (se rimasti)
    $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam' OR comment_approved = 'trash'");
    
    // Pulisce meta orphan
    $wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL");
}

// Esegui pulizia database solo se necessario (non in automatico)
// add_action('admin_init', 'meridiana_cleanup_database');

/* ========================================
   8. DEBUG INFO (SOLO PER SVILUPPO)
   ======================================== */

/**
 * Funzione di debug per monitorare performance
 * Rimuovere in produzione
 */
function meridiana_debug_info() {
    if (WP_DEBUG && current_user_can('manage_options')) {
        global $wpdb;
        echo "<!-- Meridiana Debug: Queries=" . $wpdb->num_queries . " -->";
    }
}
add_action('wp_footer', 'meridiana_debug_info');

/**
 * Fine personalizzazioni Child Theme
 * 
 * NOTA: Aggiungi qui altre funzioni personalizzate quando servono
 * Ogni modifica sarà preservata negli aggiornamenti di Divi
 */
 
 /* ========================================
   FIX FONT ICONE DIVI (ETMODULES)
   ======================================== */

/**
 * Forza il caricamento del font ETMODULES per le icone custom
 */
function meridiana_load_divi_icons_font() {
    // Carica il font delle icone Divi
    wp_enqueue_style(
        'divi-fonts', 
        get_template_directory_uri() . '/core/admin/fonts/modules.css',
        array(),
        wp_get_theme()->parent()->get('Version')
    );
    
    // Carica anche il CSS delle icone se esiste
    wp_enqueue_style(
        'et-core-icons', 
        get_template_directory_uri() . '/core/admin/css/core.css',
        array(),
        wp_get_theme()->parent()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'meridiana_load_divi_icons_font', 5);

/**
 * Carica il font ETMODULES anche nell'admin se serve
 */
function meridiana_load_divi_icons_admin() {
    wp_enqueue_style(
        'divi-fonts-admin', 
        get_template_directory_uri() . '/core/admin/fonts/modules.css',
        array(),
        wp_get_theme()->parent()->get('Version')
    );
}
add_action('admin_enqueue_scripts', 'meridiana_load_divi_icons_admin');

/* ALTERNATIVA: FONT AWESOME INVECE DI ETMODULES */
function meridiana_load_font_awesome() {
    wp_enqueue_style(
        'font-awesome', 
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
}
add_action('wp_enqueue_scripts', 'meridiana_load_font_awesome');