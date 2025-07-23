<?php
/**
 * Modulo Admin Only - Child Theme La Meridiana
 * 
 * Contiene funzioni che devono essere caricate SOLO nell'admin:
 * - Ottimizzazioni backend
 * - Utility per gestione mass actions
 * - Tools di manutenzione
 * - Debug info avanzate
 * 
 * @version 1.0.0
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Verifica che siamo davvero nell'admin
if (!is_admin()) {
    return;
}

/* ========================================
   BULK ACTIONS PERSONALIZZATE
   ======================================== */

/**
 * Aggiunge azioni di massa personalizzate per protocolli
 */
function meridiana_add_bulk_actions_protocolli($bulk_actions) {
    $bulk_actions['meridiana_set_pianificazione'] = 'Imposta come Pianificazione ATS';
    $bulk_actions['meridiana_remove_pianificazione'] = 'Rimuovi da Pianificazione ATS';
    return $bulk_actions;
}
add_filter('bulk_actions-edit-protocollo', 'meridiana_add_bulk_actions_protocolli');

/**
 * Gestisce le azioni di massa personalizzate
 */
function meridiana_handle_bulk_actions($redirect_to, $action, $post_ids) {
    
    if (!current_user_can('edit_posts')) {
        return $redirect_to;
    }
    
    $processed = 0;
    
    switch ($action) {
        case 'meridiana_set_pianificazione':
            foreach ($post_ids as $post_id) {
                if (get_post_type($post_id) === 'protocollo') {
                    update_field('pianificazione_ats', true, $post_id);
                    $processed++;
                }
            }
            $redirect_to = add_query_arg('meridiana_pianificazione_set', $processed, $redirect_to);
            break;
            
        case 'meridiana_remove_pianificazione':
            foreach ($post_ids as $post_id) {
                if (get_post_type($post_id) === 'protocollo') {
                    update_field('pianificazione_ats', false, $post_id);
                    $processed++;
                }
            }
            $redirect_to = add_query_arg('meridiana_pianificazione_removed', $processed, $redirect_to);
            break;
    }
    
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-protocollo', 'meridiana_handle_bulk_actions', 10, 3);

/**
 * Mostra notifiche per le azioni di massa
 */
function meridiana_bulk_action_notices() {
    if (!empty($_REQUEST['meridiana_pianificazione_set'])) {
        $count = intval($_REQUEST['meridiana_pianificazione_set']);
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . sprintf(_n('%d protocollo impostato come Pianificazione ATS.', '%d protocolli impostati come Pianificazione ATS.', $count), $count) . '</p>';
        echo '</div>';
    }
    
    if (!empty($_REQUEST['meridiana_pianificazione_removed'])) {
        $count = intval($_REQUEST['meridiana_pianificazione_removed']);
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . sprintf(_n('%d protocollo rimosso da Pianificazione ATS.', '%d protocolli rimossi da Pianificazione ATS.', $count), $count) . '</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'meridiana_bulk_action_notices');

/* ========================================
   FILTRI ADMIN PERSONALIZZATI
   ======================================== */

/**
 * Aggiunge filtri personalizzati nella lista protocolli
 */
function meridiana_add_admin_filters() {
    global $typenow;
    
    if ($typenow === 'protocollo') {
        // Filtro pianificazione ATS
        $current_filter = isset($_GET['pianificazione_ats']) ? $_GET['pianificazione_ats'] : '';
        
        echo '<select name="pianificazione_ats">';
        echo '<option value="">Tutti i protocolli</option>';
        echo '<option value="1"' . selected($current_filter, '1', false) . '>Solo Pianificazione ATS</option>';
        echo '<option value="0"' . selected($current_filter, '0', false) . '>Solo Protocolli Normali</option>';
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'meridiana_add_admin_filters');

/**
 * Applica i filtri personalizzati alla query
 */
function meridiana_apply_admin_filters($query) {
    global $pagenow, $typenow;
    
    if ($pagenow === 'edit.php' && $typenow === 'protocollo' && isset($_GET['pianificazione_ats']) && $_GET['pianificazione_ats'] !== '') {
        $meta_value = $_GET['pianificazione_ats'] === '1' ? '1' : '0';
        
        $meta_query = array(
            array(
                'key' => 'pianificazione_ats',
                'value' => $meta_value,
                'compare' => '='
            )
        );
        
        $query->set('meta_query', $meta_query);
    }
}
add_filter('parse_query', 'meridiana_apply_admin_filters');

/* ========================================
   TOOLS DI MANUTENZIONE
   ======================================== */

/**
 * Aggiunge pagina di tools nel menu admin
 */
function meridiana_add_tools_menu() {
    add_submenu_page(
        'tools.php',
        'La Meridiana Tools',
        'La Meridiana Tools',
        'manage_options',
        'meridiana-tools',
        'meridiana_tools_page'
    );
}
add_action('admin_menu', 'meridiana_add_tools_menu');

/**
 * Pagina tools di manutenzione
 */
function meridiana_tools_page() {
    
    if (!current_user_can('manage_options')) {
        wp_die('Non hai i permessi per accedere a questa pagina.');
    }
    
    // Gestisci azioni
    if (isset($_POST['action']) && wp_verify_nonce($_POST['meridiana_nonce'], 'meridiana_tools')) {
        
        switch ($_POST['action']) {
            case 'clear_cache':
                meridiana_clear_all_cache();
                echo '<div class="notice notice-success"><p>Cache pulita con successo!</p></div>';
                break;
                
            case 'regenerate_counts':
                meridiana_regenerate_document_counts();
                echo '<div class="notice notice-success"><p>Conteggi documenti rigenerati!</p></div>';
                break;
                
            case 'check_missing_pdfs':
                $missing = meridiana_check_all_missing_pdfs();
                echo '<div class="notice notice-info"><p>Trovati ' . count($missing) . ' documenti senza PDF.</p></div>';
                break;
        }
    }
    
    $counts = meridiana_get_document_counts();
    ?>
    
    <div class="wrap">
        <h1>🛠️ La Meridiana Tools</h1>
        
        <div class="card" style="max-width: 800px;">
            <h2>Statistiche Sistema</h2>
            <table class="widefat">
                <tr>
                    <td><strong>Protocolli pubblicati:</strong></td>
                    <td><?php echo $counts['protocolli']; ?></td>
                </tr>
                <tr>
                    <td><strong>Moduli pubblicati:</strong></td>
                    <td><?php echo $counts['moduli']; ?></td>
                </tr>
                <tr>
                    <td><strong>Pianificazione ATS:</strong></td>
                    <td><?php echo $counts['pianificazione']; ?></td>
                </tr>
                <tr>
                    <td><strong>Totale documenti:</strong></td>
                    <td><?php echo $counts['totale']; ?></td>
                </tr>
                <tr>
                    <td><strong>Child Theme versione:</strong></td>
                    <td><?php echo MERIDIANA_CHILD_VERSION; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card" style="max-width: 800px;">
            <h2>Tools di Manutenzione</h2>
            
            <form method="post">
                <?php wp_nonce_field('meridiana_tools', 'meridiana_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Pulisci Cache</th>
                        <td>
                            <button type="submit" name="action" value="clear_cache" class="button">
                                🗑️ Pulisci Cache Documenti
                            </button>
                            <p class="description">Pulisce tutte le cache dei conteggi e documenti recenti.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Rigenera Conteggi</th>
                        <td>
                            <button type="submit" name="action" value="regenerate_counts" class="button">
                                🔄 Rigenera Statistiche
                            </button>
                            <p class="description">Ricalcola tutti i conteggi dei documenti.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Controllo PDF</th>
                        <td>
                            <button type="submit" name="action" value="check_missing_pdfs" class="button">
                                🔍 Controlla PDF Mancanti
                            </button>
                            <p class="description">Verifica quali documenti non hanno PDF associati.</p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        
        <?php if (WP_DEBUG): ?>
        <div class="card" style="max-width: 800px;">
            <h2>Debug Info (solo in modalità debug)</h2>
            <textarea style="width: 100%; height: 200px; font-family: monospace;"><?php
                echo "WordPress Version: " . get_bloginfo('version') . "\n";
                echo "PHP Version: " . PHP_VERSION . "\n";
                echo "Divi Version: " . wp_get_theme()->parent()->get('Version') . "\n";
                echo "Child Theme Version: " . MERIDIANA_CHILD_VERSION . "\n";
                echo "Active Plugins: " . count(get_option('active_plugins')) . "\n";
                echo "Memory Limit: " . ini_get('memory_limit') . "\n";
                echo "Max Upload Size: " . size_format(wp_max_upload_size()) . "\n";
                echo "ACF Active: " . (function_exists('get_field') ? 'Yes' : 'No') . "\n";
                echo "LearnDash Active: " . (function_exists('learndash_get_setting') ? 'Yes' : 'No') . "\n";
            ?></textarea>
        </div>
        <?php endif; ?>
        
    </div>
    
    <?php
}

/* ========================================
   FUNZIONI UTILITY TOOLS
   ======================================== */

/**
 * Pulisce tutte le cache
 */
function meridiana_clear_all_cache() {
    wp_cache_delete('meridiana_document_counts');
    wp_cache_delete('meridiana_recent_docs_5_30');
    wp_cache_delete('meridiana_recent_docs_10_30');
    
    // Pulisci anche cache WordPress
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    if (function_exists('meridiana_log')) {
        meridiana_log('All cache cleared via tools page');
    }
}

/**
 * Rigenera conteggi documenti
 */
function meridiana_regenerate_document_counts() {
    // Prima pulisci cache
    meridiana_clear_all_cache();
    
    // Poi forza ricalcolo
    meridiana_get_document_counts();
    
    if (function_exists('meridiana_log')) {
        meridiana_log('Document counts regenerated via tools page');
    }
}

/**
 * Controlla documenti senza PDF
 */
function meridiana_check_all_missing_pdfs() {
    $missing = array();
    
    // Controlla protocolli
    $protocolli = get_posts(array(
        'post_type' => 'protocollo',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    foreach ($protocolli as $protocollo) {
        $pdf = get_field('file_pdf_del_protocollo', $protocollo->ID);
        if (empty($pdf)) {
            $missing[] = array(
                'type' => 'protocollo',
                'id' => $protocollo->ID,
                'title' => $protocollo->post_title
            );
        }
    }
    
    // Controlla moduli
    $moduli = get_posts(array(
        'post_type' => 'modulo',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    foreach ($moduli as $modulo) {
        $pdf = get_field('file_pdf_del_modulo', $modulo->ID);
        if (empty($pdf)) {
            $missing[] = array(
                'type' => 'modulo',
                'id' => $modulo->ID,
                'title' => $modulo->post_title
            );
        }
    }
    
    if (function_exists('meridiana_log')) {
        meridiana_log('Missing PDFs check: ' . count($missing) . ' documents without PDF');
    }
    
    return $missing;
}

/* ========================================
   PERFORMANCE MONITORING AVANZATO
   ======================================== */

/**
 * Monitor query lente nell'admin
 */
function meridiana_admin_slow_query_monitor() {
    if (!WP_DEBUG) {
        return;
    }
    
    global $wpdb;
    
    if ($wpdb->num_queries > 100) {
        add_action('admin_notices', function() use ($wpdb) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Performance Warning:</strong> Questa pagina ha eseguito ' . $wpdb->num_queries . ' query database. ';
            echo 'Considera di ottimizzare o contatta lo sviluppatore.</p>';
            echo '</div>';
        });
    }
}
add_action('admin_footer', 'meridiana_admin_slow_query_monitor');

/* ========================================
   BACKUP E EXPORT UTILITIES
   ======================================== */

/**
 * Esporta configurazione ACF (per backup)
 */
function meridiana_export_acf_config() {
    if (!current_user_can('manage_options')) {
        return false;
    }
    
    if (!function_exists('acf_get_field_groups')) {
        return false;
    }
    
    $field_groups = acf_get_field_groups();
    $export = array();
    
    foreach ($field_groups as $group) {
        // Solo i nostri field groups
        if (strpos($group['title'], 'Meridiana') !== false || 
            strpos($group['title'], 'protocolli') !== false ||
            strpos($group['title'], 'moduli') !== false) {
            
            $export[] = $group;
        }
    }
    
    return json_encode($export, JSON_PRETTY_PRINT);
}

/**
 * Menu per export configurazione
 */
function meridiana_add_export_menu() {
    add_submenu_page(
        'meridiana-tools',
        'Export Configurazione',
        'Export Config',
        'manage_options',
        'meridiana-export',
        'meridiana_export_page'
    );
}
add_action('admin_menu', 'meridiana_add_export_menu', 20);

/**
 * Pagina export configurazione
 */
function meridiana_export_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Non hai i permessi per accedere a questa pagina.');
    }
    
    $config = meridiana_export_acf_config();
    
    ?>
    <div class="wrap">
        <h1>📦 Export Configurazione La Meridiana</h1>
        
        <div class="card">
            <h2>Configurazione ACF Field Groups</h2>
            <p>Copia il codice seguente per fare backup della configurazione dei campi personalizzati:</p>
            
            <textarea style="width: 100%; height: 400px; font-family: monospace;" readonly><?php
                echo esc_textarea($config);
            ?></textarea>
            
            <p><em>Salva questo contenuto in un file .json per backup della configurazione.</em></p>
        </div>
    </div>
    <?php
}

/**
 * Fine modulo admin only
 */