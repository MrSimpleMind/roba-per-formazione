<?php
/**
 * Functions.php del Child Theme Divi 5 - La Meridiana
 * 
 * Questo è il file principale che carica tutti i moduli
 * Ogni funzionalità è organizzata in file separati per migliore manutenzione
 * 
 * @version 1.0.0
 * @author La Meridiana
 */

// Sicurezza: previene accesso diretto al file
if (!defined('ABSPATH')) {
    exit;
}

/* ========================================
   CONFIGURAZIONE CHILD THEME
   ======================================== */

// Versione del child theme (per cache busting)
define('MERIDIANA_CHILD_VERSION', '1.0.0');

// Path per includes
define('MERIDIANA_CHILD_PATH', get_stylesheet_directory());
define('MERIDIANA_CHILD_URL', get_stylesheet_directory_uri());

/**
 * Carica correttamente il CSS del child theme
 */
function meridiana_child_theme_styles() {
    // Carica prima il CSS del tema padre
    wp_enqueue_style(
        'divi-parent-style', 
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme()->parent()->get('Version')
    );
    
    // Carica il CSS del child theme base
    wp_enqueue_style(
        'meridiana-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('divi-parent-style'),
        MERIDIANA_CHILD_VERSION
    );
    
    // Carica il Design System La Meridiana
    wp_enqueue_style(
        'meridiana-design-system',
        get_stylesheet_directory_uri() . '/assets/css/meridiana-design-system.css',
        array('divi-parent-style'),
        MERIDIANA_CHILD_VERSION
    );
}
add_action('wp_enqueue_scripts', 'meridiana_child_theme_styles');

/* ========================================
   CARICAMENTO MODULI
   ======================================== */

/**
 * Carica tutti i moduli del child theme
 * Ogni modulo è in un file separato per migliore organizzazione
 */

// 1. Ottimizzazioni performance (disabilitazione commenti, emoji, etc.)
require_once MERIDIANA_CHILD_PATH . '/inc/performance.php';

// 2. Sistema di ricerca unificata e AJAX
require_once MERIDIANA_CHILD_PATH . '/inc/search-system.php';

// 3. Helper functions per documenti
require_once MERIDIANA_CHILD_PATH . '/inc/document-helpers.php';

// 4. Miglioramenti admin (dashboard widget, shortcode, etc.)
require_once MERIDIANA_CHILD_PATH . '/inc/admin-enhancements.php';

// 5. Carica moduli opzionali solo se necessari
if (is_admin()) {
    // Funzioni solo per admin
    require_once MERIDIANA_CHILD_PATH . '/inc/admin-only.php';
}

/* ========================================
   HOOK DI ATTIVAZIONE/DISATTIVAZIONE
   ======================================== */

/**
 * Hook eseguito quando il child theme viene attivato
 */
function meridiana_child_theme_activation() {
    // Flush rewrite rules per assicurarsi che i permalink funzionino
    flush_rewrite_rules();
    
    // Log attivazione se in debug mode
    if (WP_DEBUG) {
        error_log('Child Theme La Meridiana attivato - versione ' . MERIDIANA_CHILD_VERSION);
    }
}
add_action('after_switch_theme', 'meridiana_child_theme_activation');

/**
 * Verifica compatibilità con Divi
 */
function meridiana_check_divi_compatibility() {
    $theme = wp_get_theme();
    $parent_theme = $theme->parent();
    
    if (!$parent_theme || $parent_theme->get('Name') !== 'Divi') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Attenzione:</strong> Il child theme La Meridiana richiede il tema Divi come tema padre.</p>';
            echo '</div>';
        });
    }
}
add_action('admin_init', 'meridiana_check_divi_compatibility');

/* ========================================
   COSTANTI E CONFIGURAZIONI GLOBALI
   ======================================== */

// Configurazioni per il sistema di ricerca
define('MERIDIANA_SEARCH_POSTS_PER_PAGE', 12);
define('MERIDIANA_SEARCH_DEBOUNCE_MS', 500);

// Configurazioni per tracking (future)
define('MERIDIANA_ENABLE_TRACKING', true);
define('MERIDIANA_ENABLE_ANALYTICS', WP_DEBUG);

/* ========================================
   DEBUG E MONITORING
   ======================================== */

/**
 * Informazioni di debug per sviluppo
 */
if (WP_DEBUG && current_user_can('manage_options')) {
    
    function meridiana_debug_info() {
        global $wpdb;
        $memory_usage = round(memory_get_peak_usage() / 1024 / 1024, 2);
        
        echo "<!-- Meridiana Debug Info:";
        echo " Queries: " . $wpdb->num_queries;
        echo " | Memory: " . $memory_usage . "MB";
        echo " | Child Theme: " . MERIDIANA_CHILD_VERSION;
        echo " -->";
    }
    add_action('wp_footer', 'meridiana_debug_info');
    
    // Log errori specifici del child theme
    function meridiana_log_error($message, $context = '') {
        $log_message = "[MERIDIANA] " . $message;
        if (!empty($context)) {
            $log_message .= " | Context: " . $context;
        }
        error_log($log_message);
    }
    
    // Rendi disponibile la funzione globalmente
    if (!function_exists('meridiana_log')) {
        function meridiana_log($message, $context = '') {
            meridiana_log_error($message, $context);
        }
    }
}

/**
 * Fine configurazione child theme
 * 
 * NOTA: Tutte le funzionalità specifiche sono nei file /inc/
 * Questo file rimane pulito e facile da mantenere
 */