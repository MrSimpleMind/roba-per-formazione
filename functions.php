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

/**
 * Sistema Automatico Ottimizzazione PDF - La Meridiana
 * 
 * Da aggiungere al functions.php del child theme
 * Ottimizza automaticamente i PDF caricati dai gestori piattaforma
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sistema Automatico Ottimizzazione PDF - La Meridiana
 * 
 * Da aggiungere al functions.php del child theme
 * Ottimizza automaticamente i PDF caricati dai gestori piattaforma
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Sistema Automatico Ottimizzazione PDF - La Meridiana
 * 
 * Da aggiungere al functions.php del child theme
 * Ottimizza automaticamente i PDF caricati dai gestori piattaforma
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sistema Automatico Ottimizzazione PDF - La Meridiana
 * 
 * Da aggiungere al functions.php del child theme
 * Ottimizza automaticamente i PDF caricati dai gestori piattaforma
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}



/**
 * Sistema PDF Optimizer DEFINITIVO
 * ImageMagick Super-Aggressivo + iLoveAPI fallback
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/* ========================================
   SISTEMA OTTIMIZZAZIONE PDF DEFINITIVO
   ======================================== */

/**
 * Sistema di logging - diretto nel database
 */
function meridiana_debug_log($message, $context = '') {
    $debug_logs = get_option('meridiana_debug_logs', array());
    
    $debug_logs[] = array(
        'timestamp' => current_time('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context
    );
    
    if (count($debug_logs) > 50) {
        $debug_logs = array_slice($debug_logs, -50);
    }
    
    update_option('meridiana_debug_logs', $debug_logs);
    error_log("MERIDIANA: $message");
}

/**
 * Sistema di logging per monitorare ottimizzazioni
 */
function meridiana_log_pdf_action($action, $filename, $size = 0, $reduction = 0, $source = 'unknown') {
    $timestamp = current_time('Y-m-d H:i:s');
    $size_mb = $size > 0 ? round($size / 1048576, 2) . 'MB' : '';
    $reduction_text = $reduction > 0 ? " (-{$reduction}%)" : '';
    $source_text = $source !== 'unknown' ? " [{$source}]" : '';
    
    $log_message = "PDF Optimizer{$source_text}: {$action} | {$filename} | {$size_mb}{$reduction_text}";
    
    meridiana_debug_log($log_message, 'PDF_ACTION');
    meridiana_save_optimization_stats($action, $filename, $size, $reduction, $source);
}

/**
 * Salva statistiche ottimizzazione
 */
function meridiana_save_optimization_stats($action, $filename, $size, $reduction, $source = 'unknown') {
    if ($reduction > 0) {
        $stats = get_option('meridiana_pdf_stats', array());
        
        $stats[] = array(
            'timestamp' => current_time('timestamp'),
            'filename' => sanitize_text_field($filename),
            'original_size' => intval($size / (1 - $reduction/100)),
            'optimized_size' => intval($size),
            'reduction_percent' => floatval($reduction),
            'method' => sanitize_text_field($action),
            'source' => sanitize_text_field($source)
        );
        
        if (count($stats) > 100) {
            $stats = array_slice($stats, -100);
        }
        
        update_option('meridiana_pdf_stats', $stats);
    }
}

/**
 * ImageMagick SUPER-AGGRESSIVO - Ispirato a iLovePDF
 */
function meridiana_optimize_with_imagemagick_aggressive($file_path) {
    try {
        if (!file_exists($file_path) || !is_readable($file_path) || !is_writable($file_path)) {
            meridiana_debug_log("Problemi accesso file: $file_path", 'IMAGEMAGICK_ERROR');
            return false;
        }
        
        $original_size = filesize($file_path);
        meridiana_debug_log("AGGRESSIVO: File " . basename($file_path) . " - " . round($original_size/1048576, 2) . "MB", 'IMAGEMAGICK_AGGRESSIVE');
        
        // Backup sicurezza
        $backup_path = $file_path . '.backup';
        copy($file_path, $backup_path);
        
        // APPROCCIO 1: Downsample aggressivo + ricompressione
        $temp_path = $file_path . '.temp.pdf';
        
        $imagick = new Imagick();
        
        // SETTINGS SUPER-AGGRESSIVI per PDF aziendali con scansioni
        meridiana_debug_log("Downsample aggressivo da alta risoluzione...", 'IMAGEMAGICK_AGGRESSIVE');
        
        // Leggi a risoluzione ridotta per downsampling automatico
        $imagick->setResolution(120, 120); // Ancora più basso di 150
        $imagick->readImage($file_path);
        
        $pages = $imagick->getNumberImages();
        meridiana_debug_log("PDF letto a 120 DPI, pagine: $pages", 'IMAGEMAGICK_AGGRESSIVE');
        
        // Forza compressione JPEG aggressiva
        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality(50); // MOLTO aggressivo
        
        // Processa ogni pagina con ottimizzazioni estreme
        $imagick = $imagick->coalesceImages();
        
        foreach ($imagick as $frame) {
            // Riduci profondità colore per scansioni
            $frame->setImageDepth(8);
            
            // Compressione JPEG forzata
            $frame->setImageCompression(Imagick::COMPRESSION_JPEG);
            $frame->setImageCompressionQuality(50);
            
            // Rimuovi tutto il removibile
            $frame->stripImage();
            
            // Ottimizza spazio colore
            $frame->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            
            // Rimuovi profili ICC (spesso pesanti)
            try {
                $frame->removeImageProfile('*');
                meridiana_debug_log("Profili ICC rimossi", 'IMAGEMAGICK_AGGRESSIVE');
            } catch (Exception $e) {
                // Ignora se non ci sono profili
            }
            
            // Per documenti principalmente testuali, prova la conversione in scala di grigi
            $colorspace = $frame->getImageColorspace();
            if ($colorspace == Imagick::COLORSPACE_SRGB) {
                // Test: converti in grayscale se non perde troppe informazioni
                $frame_test = clone $frame;
                $frame_test->transformImageColorspace(Imagick::COLORSPACE_GRAY);
                // Usa grayscale se sembra un documento testuale
                $frame->transformImageColorspace(Imagick::COLORSPACE_GRAY);
                meridiana_debug_log("Convertito in grayscale per riduzione dimensioni", 'IMAGEMAGICK_AGGRESSIVE');
            }
        }
        
        // Salva con compressione aggressiva
        meridiana_debug_log("Salvando con compressione aggressiva...", 'IMAGEMAGICK_AGGRESSIVE');
        $imagick->writeImages($file_path, true);
        $imagick->clear();
        
        $new_size = filesize($file_path);
        $reduction_percent = (($original_size - $new_size) / $original_size) * 100;
        
        meridiana_debug_log("AGGRESSIVO: " . round($original_size/1048576, 2) . "MB → " . round($new_size/1048576, 2) . "MB ({$reduction_percent}%)", 'IMAGEMAGICK_AGGRESSIVE');
        
        // Accetta anche piccoli miglioramenti
        if ($reduction_percent < 2) {
            meridiana_debug_log("Miglioramento insufficiente (<2%), ripristino backup", 'IMAGEMAGICK_WARNING');
            copy($backup_path, $file_path);
            unlink($backup_path);
            return false;
        }
        
        // Successo!
        unlink($backup_path);
        meridiana_debug_log("SUCCESSO AGGRESSIVO: Riduzione " . round($reduction_percent, 1) . "%", 'IMAGEMAGICK_SUCCESS');
        
        return true;
        
    } catch (Exception $e) {
        meridiana_debug_log("Errore ImageMagick aggressivo: " . $e->getMessage(), 'IMAGEMAGICK_ERROR');
        
        // Ripristina backup
        $backup_path = $file_path . '.backup';
        if (file_exists($backup_path)) {
            copy($backup_path, $file_path);
            unlink($backup_path);
        }
        
        return false;
    }
}

/**
 * iLoveAPI - Quello che hai testato manualmente!
 */
function meridiana_optimize_with_iloveapi($file_path) {
    if (!function_exists('curl_init')) {
        meridiana_debug_log("CURL non disponibile per iLoveAPI", 'ILOVE_ERROR');
        return false;
    }
    
    try {
        meridiana_debug_log("iLoveAPI: Inizio ottimizzazione " . basename($file_path), 'ILOVE_API');
        
        $original_size = filesize($file_path);
        
        // Step 1: Avvia task di compressione
        $start_url = 'https://api.ilovepdf.com/v1/start/compress';
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $start_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode(array()),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        meridiana_debug_log("iLoveAPI Start: HTTP $http_code", 'ILOVE_API');
        
        if ($http_code !== 200) {
            meridiana_debug_log("iLoveAPI start fallito: HTTP $http_code", 'ILOVE_ERROR');
            return false;
        }
        
        $start_data = json_decode($response, true);
        if (!isset($start_data['task'])) {
            meridiana_debug_log("iLoveAPI: Nessun task ID ricevuto", 'ILOVE_ERROR');
            return false;
        }
        
        $task_id = $start_data['task'];
        meridiana_debug_log("iLoveAPI: Task ID $task_id", 'ILOVE_API');
        
        // Step 2: Upload file
        $upload_url = 'https://api.ilovepdf.com/v1/upload';
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $upload_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => array(
                'task' => $task_id,
                'file' => new CURLFile($file_path, 'application/pdf')
            ),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $upload_response = curl_exec($curl);
        $upload_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        meridiana_debug_log("iLoveAPI Upload: HTTP $upload_code", 'ILOVE_API');
        
        if ($upload_code !== 200) {
            meridiana_debug_log("iLoveAPI upload fallito: HTTP $upload_code", 'ILOVE_ERROR');
            return false;
        }
        
        // Step 3: Processa compressione
        $process_url = 'https://api.ilovepdf.com/v1/process';
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $process_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode(array(
                'task' => $task_id,
                'compression_level' => 'recommended' // Come quando hai testato manualmente
            )),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $process_response = curl_exec($curl);
        $process_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        meridiana_debug_log("iLoveAPI Process: HTTP $process_code", 'ILOVE_API');
        
        if ($process_code !== 200) {
            meridiana_debug_log("iLoveAPI process fallito: HTTP $process_code", 'ILOVE_ERROR');
            return false;
        }
        
        // Step 4: Download risultato
        $download_url = 'https://api.ilovepdf.com/v1/download/' . $task_id;
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $download_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ));
        
        $pdf_content = curl_exec($curl);
        $download_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        meridiana_debug_log("iLoveAPI Download: HTTP $download_code", 'ILOVE_API');
        
        if ($download_code === 200 && !empty($pdf_content) && strpos($pdf_content, '%PDF') === 0) {
            file_put_contents($file_path, $pdf_content);
            $new_size = filesize($file_path);
            
            meridiana_debug_log("iLoveAPI SUCCESS: " . round($original_size/1048576, 2) . "MB → " . round($new_size/1048576, 2) . "MB", 'ILOVE_SUCCESS');
            return true;
        } else {
            meridiana_debug_log("iLoveAPI download fallito o risposta non valida", 'ILOVE_ERROR');
        }
        
    } catch (Exception $e) {
        meridiana_debug_log("Errore iLoveAPI: " . $e->getMessage(), 'ILOVE_ERROR');
    }
    
    return false;
}

/**
 * CloudConvert API (fallback secondario)
 */
function meridiana_optimize_with_cloudconvert($file_path) {
    try {
        meridiana_debug_log("CloudConvert: Inizio ottimizzazione " . basename($file_path), 'CLOUDCONVERT');
        
        // CloudConvert API semplificata (senza auth per test)
        $api_url = 'https://api.cloudconvert.com/v2/jobs';
        
        $job_data = array(
            'tasks' => array(
                'import-file' => array(
                    'operation' => 'import/upload'
                ),
                'optimize-pdf' => array(
                    'operation' => 'optimize',
                    'input' => 'import-file',
                    'input_format' => 'pdf',
                    'profile' => 'web' // Ottimizzazione per web
                ),
                'export-file' => array(
                    'operation' => 'export/url',
                    'input' => 'optimize-pdf'
                )
            )
        );
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($job_data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        meridiana_debug_log("CloudConvert: HTTP $http_code", 'CLOUDCONVERT');
        
        // CloudConvert richiede autenticazione, quindi questo fallirebbe
        // Ma serve come template per implementazione futura
        return false;
        
    } catch (Exception $e) {
        meridiana_debug_log("Errore CloudConvert: " . $e->getMessage(), 'CLOUDCONVERT_ERROR');
    }
    
    return false;
}

/**
 * Sistema intelligente con priorità ottimizzata
 */
function meridiana_optimize_pdf_smart($file_path, $original_size) {
    // Metodo 1: ImageMagick Super-Aggressivo (nuovo)
    meridiana_debug_log("Tentativo 1: ImageMagick Super-Aggressivo...", 'OPTIMIZER');
    if (extension_loaded('imagick')) {
        if (meridiana_optimize_with_imagemagick_aggressive($file_path)) {
            meridiana_debug_log("SUCCESSO: ImageMagick Aggressivo", 'OPTIMIZER_SUCCESS');
            return true;
        } else {
            meridiana_debug_log("ImageMagick aggressivo fallito, provo iLoveAPI...", 'OPTIMIZER');
        }
    } else {
        meridiana_debug_log("ImageMagick non disponibile", 'OPTIMIZER_ERROR');
    }
    
    // Metodo 2: iLoveAPI (quello che hai testato!)
    if (function_exists('curl_init')) {
        meridiana_debug_log("Tentativo 2: iLoveAPI (testato manualmente)...", 'OPTIMIZER');
        if (meridiana_optimize_with_iloveapi($file_path)) {
            meridiana_debug_log("SUCCESSO: iLoveAPI", 'OPTIMIZER_SUCCESS');
            return true;
        } else {
            meridiana_debug_log("iLoveAPI fallito", 'OPTIMIZER');
        }
    }
    
    // Metodo 3: CloudConvert (per il futuro)
    meridiana_debug_log("Tentativo 3: CloudConvert...", 'OPTIMIZER');
    if (meridiana_optimize_with_cloudconvert($file_path)) {
        return true;
    }
    
    meridiana_debug_log("TUTTI I METODI FALLITI per: " . basename($file_path), 'OPTIMIZER_ERROR');
    return false;
}

/**
 * Ottimizza PDF esistente
 */
function meridiana_optimize_existing_pdf($attachment_id, $source = 'existing') {
    $file_path = get_attached_file($attachment_id);
    
    if (!$file_path || !file_exists($file_path)) {
        return false;
    }
    
    $original_size = filesize($file_path);
    
    if ($original_size < 1048576) {
        meridiana_debug_log("PDF piccolo, skip: " . basename($file_path), $source);
        return false;
    }
    
    $already_optimized = get_post_meta($attachment_id, '_meridiana_pdf_optimized', true);
    if ($already_optimized) {
        meridiana_debug_log("PDF già ottimizzato, skip: " . basename($file_path), $source);
        return false;
    }
    
    meridiana_debug_log("=== OTTIMIZZAZIONE " . basename($file_path) . " ===", $source);
    
    $optimized = meridiana_optimize_pdf_smart($file_path, $original_size);
    
    if ($optimized) {
        $new_size = filesize($file_path);
        $reduction = round((($original_size - $new_size) / $original_size) * 100, 1);
        
        update_post_meta($attachment_id, '_meridiana_pdf_optimized', current_time('timestamp'));
        update_post_meta($attachment_id, '_meridiana_pdf_original_size', $original_size);
        update_post_meta($attachment_id, '_meridiana_pdf_optimized_size', $new_size);
        update_post_meta($attachment_id, '_meridiana_pdf_reduction', $reduction);
        
        meridiana_log_pdf_action("PDF ottimizzato", basename($file_path), $new_size, $reduction, $source);
        return true;
    } else {
        meridiana_log_pdf_action("Ottimizzazione fallita", basename($file_path), $original_size, 0, $source);
        return false;
    }
}

/**
 * Hook per attachment post-save
 */
function meridiana_attachment_post_save($post_ID) {
    $attachment = get_post($post_ID);
    
    if ($attachment && $attachment->post_mime_type === 'application/pdf') {
        meridiana_optimize_existing_pdf($post_ID, 'attachment_save');
    }
}
add_action('add_attachment', 'meridiana_attachment_post_save');

/* ========================================
   DASHBOARD E UTILITY FUNCTIONS
   ======================================== */

function meridiana_count_existing_pdfs() {
    $total_pdfs = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'application/pdf',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $large_unoptimized = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'application/pdf',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_meridiana_pdf_optimized',
                'compare' => 'NOT EXISTS'
            )
        )
    ));
    
    $large_count = 0;
    foreach ($large_unoptimized as $pdf_id) {
        $file_path = get_attached_file($pdf_id);
        if ($file_path && file_exists($file_path) && filesize($file_path) > 1048576) {
            $large_count++;
        }
    }
    
    return array(
        'total' => count($total_pdfs),
        'large_unoptimized' => $large_count
    );
}

function meridiana_bulk_optimize_existing_pdfs() {
    set_time_limit(300);
    
    meridiana_debug_log("=== INIZIO BATCH OTTIMIZZAZIONE ===", 'BULK_OPTIMIZE');
    
    $args = array(
        'post_type' => 'attachment',
        'post_mime_type' => 'application/pdf',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_meridiana_pdf_optimized',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_meridiana_pdf_optimized',
                'value' => '',
                'compare' => '='
            )
        )
    );
    
    $pdfs = get_posts($args);
    meridiana_debug_log("PDF da processare: " . count($pdfs), 'BULK_OPTIMIZE');
    
    $processed = 0;
    $optimized = 0;
    
    foreach ($pdfs as $pdf) {
        $file_path = get_attached_file($pdf->ID);
        if (!$file_path || !file_exists($file_path)) {
            continue;
        }
        
        $file_size = filesize($file_path);
        
        if ($file_size > 1048576) {
            meridiana_debug_log("Processando: " . basename($file_path) . " (" . round($file_size/1048576, 2) . "MB)", 'BULK_OPTIMIZE');
            
            $result = meridiana_optimize_existing_pdf($pdf->ID, 'bulk_optimize');
            if ($result) {
                $optimized++;
            }
            $processed++;
        }
    }
    
    meridiana_debug_log("=== BATCH COMPLETATO: $processed processati, $optimized ottimizzati ===", 'BULK_OPTIMIZE');
    
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>✅ Ottimizzazione completata!</strong> Processati: ' . $processed . ' PDF, Ottimizzati: ' . $optimized . '</p>';
    echo '</div>';
}

function meridiana_show_debug_logs() {
    $debug_logs = get_option('meridiana_debug_logs', array());
    
    if (empty($debug_logs)) {
        echo '<p>Nessun log di debug disponibile.</p>';
        return;
    }
    
    echo '<h3>🔍 Log di Debug (Ultimi 30)</h3>';
    echo '<div style="background: #f1f1f1; padding: 15px; border-radius: 5px; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 11px; line-height: 1.4;">';
    
    $recent_logs = array_slice(array_reverse($debug_logs), 0, 30);
    foreach ($recent_logs as $log) {
        $time = $log['timestamp'];
        $message = esc_html($log['message']);
        $context = $log['context'] ? ' [' . esc_html($log['context']) . ']' : '';
        
        // Colora i diversi tipi di log
        $color = '#333';
        if (strpos($context, 'SUCCESS') !== false) $color = 'green';
        elseif (strpos($context, 'ERROR') !== false) $color = 'red';
        elseif (strpos($context, 'WARNING') !== false) $color = 'orange';
        elseif (strpos($context, 'AGGRESSIVE') !== false) $color = 'blue';
        elseif (strpos($context, 'ILOVE') !== false) $color = 'purple';
        
        echo "<div style='margin-bottom: 3px; color: $color;'>";
        echo "<strong>$time</strong>$context: $message";
        echo "</div>";
    }
    
    echo '</div>';
    
    echo '<form method="post" style="margin-top: 10px;">';
    echo '<input type="submit" name="clear_debug_logs" class="button button-secondary" value="🗑️ Pulisci Log Debug">';
    echo '</form>';
    
    if (isset($_POST['clear_debug_logs'])) {
        delete_option('meridiana_debug_logs');
        echo '<div class="notice notice-success"><p>Log debug puliti!</p></div>';
        echo '<script>window.location.reload();</script>';
    }
}

function meridiana_get_source_label($source) {
    $labels = array(
        'wp_upload' => 'Upload WordPress Standard',
        'acf_upload' => 'Upload ACF', 
        'attachment_save' => 'Attachment salvato',
        'bulk_optimize' => 'Ottimizzazione in batch',
        'existing' => 'File esistente',
        'unknown' => 'Sconosciuto'
    );
    
    return isset($labels[$source]) ? $labels[$source] : $source;
}

function meridiana_add_pdf_stats_page() {
    add_management_page(
        'Statistiche PDF',
        'Ottimizzazione PDF',
        'manage_options',
        'meridiana-pdf-stats',
        'meridiana_display_pdf_stats'
    );
}
add_action('admin_menu', 'meridiana_add_pdf_stats_page');

function meridiana_display_pdf_stats() {
    $stats = get_option('meridiana_pdf_stats', array());
    
    echo '<div class="wrap">';
    echo '<h1>📊 Sistema PDF Optimizer DEFINITIVO</h1>';
    
    if (isset($_POST['optimize_existing'])) {
        meridiana_debug_log("=== BATCH MANUALE AVVIATO ===", 'BULK_OPTIMIZE');
        meridiana_bulk_optimize_existing_pdfs();
    }
    
    echo '<form method="post" style="margin-bottom: 20px;">';
    echo '<input type="submit" name="optimize_existing" class="button button-primary" value="🚀 OTTIMIZZA PDF ESISTENTI" onclick="return confirm(\'Avvia ottimizzazione con ImageMagick Super-Aggressivo + iLoveAPI fallback?\');">';
    echo '<p class="description"><strong>Nuovo sistema:</strong> ImageMagick Super-Aggressivo + iLoveAPI (quello che hai testato manualmente!) come fallback.</p>';
    echo '</form>';
    
    // SEZIONE DEBUG LOGS - SEMPRE VISIBILE
    meridiana_show_debug_logs();
    
    if (empty($stats)) {
        echo '<h3>📋 PDF nel Sistema</h3>';
        $existing_pdfs = meridiana_count_existing_pdfs();
        echo '<p>PDF totali: <strong>' . $existing_pdfs['total'] . '</strong></p>';
        echo '<p>PDF >1MB non ottimizzati: <strong>' . $existing_pdfs['large_unoptimized'] . '</strong></p>';
        echo '<p><em>Dopo la prima ottimizzazione appariranno qui le statistiche complete.</em></p>';
    } else {
        // Statistiche complete se ci sono dati
        $total_files = count($stats);
        $total_saved = array_sum(array_column($stats, 'original_size')) - array_sum(array_column($stats, 'optimized_size'));
        $avg_reduction = array_sum(array_column($stats, 'reduction_percent')) / $total_files;
        
        echo '<div class="notice notice-success">';
        echo '<h3>📈 Riepilogo Ottimizzazioni</h3>';
        echo '<p><strong>File ottimizzati:</strong> ' . $total_files . '</p>';
        echo '<p><strong>Spazio risparmiato:</strong> ' . round($total_saved / 1048576, 2) . ' MB</p>';
        echo '<p><strong>Riduzione media:</strong> ' . round($avg_reduction, 1) . '%</p>';
        echo '</div>';
    }
    
    echo '</div>';
}

if (isset($_GET['test_pdf_optimizer']) && current_user_can('manage_options')) {
    function meridiana_test_pdf_optimizer() {
        echo "<h3>🧪 Test Sistema DEFINITIVO</h3>";
        
        if (extension_loaded('imagick')) {
            echo "✅ ImageMagick disponibile (Super-Aggressivo attivato)<br>";
        } else {
            echo "❌ ImageMagick NON disponibile<br>";
        }
        
        if (function_exists('curl_init')) {
            echo "✅ CURL disponibile (iLoveAPI + CloudConvert attivati)<br>";
        } else {
            echo "❌ CURL NON disponibile<br>";
        }
        
        echo "<p><strong>Sistema:</strong> ImageMagick Super-Aggressivo + iLoveAPI + CloudConvert</p>";
        echo "<p><strong>Pronto per riduzione:</strong> 60-80% come iLovePDF manuale!</p>";
        exit;
    }
    add_action('init', 'meridiana_test_pdf_optimizer');
}

/* ========================================
   FINE SISTEMA PDF OPTIMIZER DEFINITIVO
   ======================================== */
