<?php
/**
 * Modulo Admin Enhancements - Child Theme La Meridiana
 * 
 * Contiene miglioramenti per l'area amministrativa:
 * - Widget dashboard
 * - Shortcode statistiche
 * - Miglioramenti interfaccia admin
 * - Notifiche e alert
 * 
 * @version 1.0.0
 */

// Sicurezza: previene accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/* ========================================
   DASHBOARD WIDGET
   ======================================== */

/**
 * Aggiunge widget statistiche al wp-admin dashboard
 */
function meridiana_add_dashboard_widget() {
    // Solo per utenti che possono gestire opzioni
    if (current_user_can('manage_options') || current_user_can('edit_posts')) {
        wp_add_dashboard_widget(
            'meridiana_docs_stats_widget',
            '📚 Statistiche Documenti La Meridiana',
            'meridiana_dashboard_widget_content',
            'meridiana_dashboard_widget_config'
        );
    }
}
add_action('wp_dashboard_setup', 'meridiana_add_dashboard_widget');

/**
 * Contenuto del widget dashboard
 */
function meridiana_dashboard_widget_content() {
    $counts = meridiana_get_document_counts();
    $recent_docs = meridiana_get_recent_documents(5);
    
    echo '<div class="meridiana-dashboard-stats">';
    
    // Statistiche conteggi
    echo '<div class="stats-overview">';
    echo '<h4 style="margin-top: 0; color: #AB1120;">📊 Documenti Pubblicati</h4>';
    echo '<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-bottom: 20px;">';
    
    // Card protocolli
    echo '<div class="stat-card" style="background: #E8F4FD; padding: 12px; border-radius: 8px; text-align: center;">';
    echo '<div style="font-size: 24px; font-weight: bold; color: #1976D2;">' . $counts['protocolli'] . '</div>';
    echo '<div style="font-size: 12px; color: #1976D2;">📄 Protocolli</div>';
    echo '</div>';
    
    // Card moduli
    echo '<div class="stat-card" style="background: #E8F5E8; padding: 12px; border-radius: 8px; text-align: center;">';
    echo '<div style="font-size: 24px; font-weight: bold; color: #388E3C;">' . $counts['moduli'] . '</div>';
    echo '<div style="font-size: 12px; color: #388E3C;">📋 Moduli</div>';
    echo '</div>';
    
    // Card pianificazione
    echo '<div class="stat-card" style="background: #FFF3E0; padding: 12px; border-radius: 8px; text-align: center;">';
    echo '<div style="font-size: 24px; font-weight: bold; color: #F57C00;">' . $counts['pianificazione'] . '</div>';
    echo '<div style="font-size: 12px; color: #F57C00;">⚠️ Pianific. ATS</div>';
    echo '</div>';
    
    // Card totale
    echo '<div class="stat-card" style="background: #F3E5F5; padding: 12px; border-radius: 8px; text-align: center;">';
    echo '<div style="font-size: 24px; font-weight: bold; color: #AB1120;">' . $counts['totale'] . '</div>';
    echo '<div style="font-size: 12px; color: #AB1120;">📚 Totali</div>';
    echo '</div>';
    
    echo '</div>'; // fine stats-grid
    echo '</div>'; // fine stats-overview
    
    // Documenti recenti
    if (!empty($recent_docs)) {
        echo '<div class="recent-documents">';
        echo '<h4 style="color: #AB1120; margin-bottom: 10px;">🆕 Documenti Recenti</h4>';
        echo '<ul style="margin: 0; padding-left: 20px;">';
        
        foreach ($recent_docs as $doc) {
            $doc_type = meridiana_get_document_type($doc->post_type, $doc->ID);
            $edit_url = get_edit_post_link($doc->ID);
            $view_url = get_permalink($doc->ID);
            $date = meridiana_format_document_date($doc->post_date);
            
            echo '<li style="margin-bottom: 8px; font-size: 13px;">';
            echo '<strong style="color: ' . $doc_type['color'] . ';">' . $doc_type['label'] . '</strong><br>';
            echo '<a href="' . esc_url($edit_url) . '" title="Modifica documento">' . esc_html($doc->post_title) . '</a>';
            echo '<br><small style="color: #666;">' . $date . ' - ';
            echo '<a href="' . esc_url($view_url) . '" target="_blank" title="Visualizza documento">Visualizza</a>';
            echo '</small>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    // Collegamenti rapidi
    echo '<div class="quick-links" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">';
    echo '<h4 style="color: #AB1120; margin-bottom: 10px;">🔗 Collegamenti Rapidi</h4>';
    echo '<p style="margin: 0;">';
    echo '<a href="' . admin_url('edit.php?post_type=protocollo') . '" class="button button-secondary">Gestisci Protocolli</a> ';
    echo '<a href="' . admin_url('edit.php?post_type=modulo') . '" class="button button-secondary">Gestisci Moduli</a> ';
    echo '<a href="' . home_url('/documenti/') . '" class="button button-primary" target="_blank">Vai alla Ricerca</a>';
    echo '</p>';
    echo '</div>';
    
    echo '</div>'; // fine meridiana-dashboard-stats
}

/**
 * Configurazione del widget dashboard (opzionale)
 */
function meridiana_dashboard_widget_config() {
    // Per ora non serve configurazione
    echo '<p>Widget per visualizzare rapidamente le statistiche dei documenti La Meridiana.</p>';
}

/* ========================================
   SHORTCODE STATISTICHE
   ======================================== */

/**
 * Shortcode per mostrare statistiche documenti nelle pagine
 * Uso: [meridiana_docs_stats show_counts="true" show_recent="true" limit="5"]
 */
function meridiana_docs_stats_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_counts' => 'true',
        'show_recent' => 'true',
        'limit' => 5,
        'style' => 'default' // default, compact, cards
    ), $atts);
    
    $counts = meridiana_get_document_counts();
    $show_counts = ($atts['show_counts'] === 'true');
    $show_recent = ($atts['show_recent'] === 'true');
    $limit = max(1, min(20, intval($atts['limit'])));
    
    $html = '<div class="meridiana-docs-stats meridiana-stats-' . esc_attr($atts['style']) . '">';
    
    if ($show_counts) {
        $html .= '<div class="stats-counts">';
        
        if ($atts['style'] === 'cards') {
            $html .= '<h4 style="text-align: center; color: #AB1120; margin-bottom: 20px;">📊 Documenti Disponibili</h4>';
            $html .= '<div class="stats-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">';
            
            // Card protocolli
            $html .= '<div class="stat-card-public" style="background: #E8F4FD; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #BBDEFB;">';
            $html .= '<div style="font-size: 32px; font-weight: bold; color: #1976D2; margin-bottom: 5px;">' . $counts['protocolli'] . '</div>';
            $html .= '<div style="font-size: 14px; color: #1976D2; font-weight: 600;">📄 Protocolli</div>';
            $html .= '</div>';
            
            // Card moduli
            $html .= '<div class="stat-card-public" style="background: #E8F5E8; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #C8E6C9;">';
            $html .= '<div style="font-size: 32px; font-weight: bold; color: #388E3C; margin-bottom: 5px;">' . $counts['moduli'] . '</div>';
            $html .= '<div style="font-size: 14px; color: #388E3C; font-weight: 600;">📋 Moduli</div>';
            $html .= '</div>';
            
            // Card pianificazione
            $html .= '<div class="stat-card-public" style="background: #FFF3E0; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #FFE0B2;">';
            $html .= '<div style="font-size: 32px; font-weight: bold; color: #F57C00; margin-bottom: 5px;">' . $counts['pianificazione'] . '</div>';
            $html .= '<div style="font-size: 14px; color: #F57C00; font-weight: 600;">⚠️ Pianificazione ATS</div>';
            $html .= '</div>';
            
            // Card totale
            $html .= '<div class="stat-card-public" style="background: #F3E5F5; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #E1BEE7;">';
            $html .= '<div style="font-size: 32px; font-weight: bold; color: #AB1120; margin-bottom: 5px;">' . $counts['totale'] . '</div>';
            $html .= '<div style="font-size: 14px; color: #AB1120; font-weight: 600;">📚 Totale</div>';
            $html .= '</div>';
            
            $html .= '</div>'; // fine stats-cards
            
        } else {
            // Stile default/compact
            $html .= '<h4 style="color: #AB1120;">📊 Documenti Disponibili</h4>';
            $html .= '<ul class="stats-list" style="list-style: none; padding: 0; margin: 0 0 20px 0;">';
            $html .= '<li style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>📄 Protocolli:</strong> ' . $counts['protocolli'] . '</li>';
            $html .= '<li style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>📋 Moduli:</strong> ' . $counts['moduli'] . '</li>';
            $html .= '<li style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>⚠️ Pianificazione ATS:</strong> ' . $counts['pianificazione'] . '</li>';
            $html .= '<li style="padding: 8px 0; color: #AB1120; font-weight: bold;"><strong>📚 Totale:</strong> ' . $counts['totale'] . '</li>';
            $html .= '</ul>';
        }
        
        $html .= '</div>'; // fine stats-counts
    }
    
    if ($show_recent) {
        $recent_docs = meridiana_get_recent_documents($limit);
        
        if ($recent_docs) {
            $html .= '<div class="stats-recent">';
            $html .= '<h4 style="color: #AB1120;">🆕 Documenti Recenti</h4>';
            $html .= '<ul class="recent-list" style="list-style: none; padding: 0; margin: 0;">';
            
            foreach ($recent_docs as $doc) {
                $doc_type = meridiana_get_document_type($doc->post_type, $doc->ID);
                $permalink = get_permalink($doc->ID);
                $date = meridiana_format_document_date($doc->post_date);
                
                $html .= '<li style="padding: 10px 0; border-bottom: 1px solid #eee;">';
                $html .= '<div>';
                $html .= '<span style="color: ' . $doc_type['color'] . '; font-weight: 600; font-size: 12px;">' . $doc_type['label'] . '</span><br>';
                $html .= '<a href="' . esc_url($permalink) . '" style="font-weight: 500; text-decoration: none;" target="_blank">';
                $html .= esc_html($doc->post_title);
                $html .= '</a><br>';
                $html .= '<small style="color: #666;">' . $date . '</small>';
                $html .= '</div>';
                $html .= '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>'; // fine stats-recent
        }
    }
    
    $html .= '</div>'; // fine meridiana-docs-stats
    
    return $html;
}
add_shortcode('meridiana_docs_stats', 'meridiana_docs_stats_shortcode');

/* ========================================
   SHORTCODE RICERCA RAPIDA
   ======================================== */

/**
 * Shortcode per box ricerca rapida
 * Uso: [meridiana_search_box placeholder="Cerca documenti..." button_text="Cerca"]
 */
function meridiana_search_box_shortcode($atts) {
    $atts = shortcode_atts(array(
        'placeholder' => 'Cerca documenti, protocolli, moduli...',
        'button_text' => 'Cerca',
        'show_filters' => 'false',
        'style' => 'default'
    ), $atts);
    
    $search_url = home_url('/documenti/');
    
    $html = '<div class="meridiana-search-box meridiana-search-' . esc_attr($atts['style']) . '">';
    $html .= '<form method="GET" action="' . esc_url($search_url) . '" class="search-form">';
    
    if ($atts['style'] === 'hero') {
        $html .= '<div style="background: linear-gradient(135deg, #AB1120 0%, #8A0E1A 100%); padding: 40px 30px; border-radius: 15px; text-align: center; color: white; margin: 20px 0;">';
        $html .= '<h3 style="color: white; margin: 0 0 20px 0; font-size: 24px;">🔍 Cerca nella Documentazione</h3>';
        $html .= '<div style="display: flex; gap: 10px; max-width: 600px; margin: 0 auto;">';
        $html .= '<input type="text" name="search_text" placeholder="' . esc_attr($atts['placeholder']) . '" ';
        $html .= 'style="flex: 1; padding: 15px 20px; border: none; border-radius: 8px; font-size: 16px;" />';
        $html .= '<button type="submit" style="background: white; color: #AB1120; border: none; padding: 15px 25px; border-radius: 8px; font-weight: 600; cursor: pointer;">';
        $html .= esc_html($atts['button_text']);
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        
    } else {
        // Stile default
        $html .= '<div style="display: flex; gap: 10px; margin: 15px 0; align-items: center;">';
        $html .= '<input type="text" name="search_text" placeholder="' . esc_attr($atts['placeholder']) . '" ';
        $html .= 'style="flex: 1; padding: 12px 16px; border: 2px solid #EAEAEA; border-radius: 8px; font-size: 16px;" />';
        $html .= '<button type="submit" style="background: #AB1120; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">';
        $html .= esc_html($atts['button_text']);
        $html .= '</button>';
        $html .= '</div>';
    }
    
    $html .= '</form>';
    $html .= '</div>';
    
    return $html;
}
add_shortcode('meridiana_search_box', 'meridiana_search_box_shortcode');

/* ========================================
   MIGLIORAMENTI INTERFACCIA ADMIN
   ======================================== */

/**
 * Aggiunge CSS personalizzato all'admin per i nostri CPT
 */
function meridiana_admin_styles() {
    global $post_type;
    
    // Solo per i nostri CPT
    if (in_array($post_type, array('protocollo', 'modulo', 'convenzione', 'contatto', 'salute'))) {
        ?>
        <style>
        /* Stili per admin La Meridiana */
        .post-type-<?php echo $post_type; ?> #postbox-container-2 .meta-box-sortables {
            min-height: 0;
        }
        
        .post-type-<?php echo $post_type; ?> .acf-field {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e1e1e1;
        }
        
        .post-type-<?php echo $post_type; ?> .acf-label {
            font-weight: 600;
            color: #AB1120;
        }
        
        /* Highlight per campi obbligatori */
        .post-type-<?php echo $post_type; ?> .acf-field.acf-field-required .acf-label:after {
            content: " *";
            color: #e74c3c;
            font-weight: bold;
        }
        
        /* Stile per il widget dashboard */
        #meridiana_docs_stats_widget .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        #meridiana_docs_stats_widget .stat-card {
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }
        </style>
        <?php
    }
}
add_action('admin_head', 'meridiana_admin_styles');

/**
 * Aggiunge colonne personalizzate alle liste admin
 */
function meridiana_add_admin_columns($columns) {
    global $post_type;
    
    if ($post_type === 'protocollo') {
        // Aggiungi colonna per pianificazione ATS
        $columns['pianificazione_ats'] = 'Pianificazione ATS';
        $columns['udo'] = 'UDO';
        $columns['profili'] = 'Profili';
    }
    
    if ($post_type === 'modulo') {
        $columns['aree_competenza'] = 'Aree Competenza';
        $columns['udo'] = 'UDO';
    }
    
    return $columns;
}
add_filter('manage_protocollo_posts_columns', 'meridiana_add_admin_columns');
add_filter('manage_modulo_posts_columns', 'meridiana_add_admin_columns');

/**
 * Popola le colonne personalizzate
 */
function meridiana_fill_admin_columns($column, $post_id) {
    switch ($column) {
        case 'pianificazione_ats':
            $is_pianificazione = get_field('pianificazione_ats', $post_id);
            echo $is_pianificazione ? '⚠️ Sì' : '—';
            break;
            
        case 'udo':
            $terms = get_the_terms($post_id, 'unita_di_offerta');
            if ($terms && !is_wp_error($terms)) {
                $names = wp_list_pluck($terms, 'name');
                echo esc_html(implode(', ', array_slice($names, 0, 2)));
                if (count($names) > 2) {
                    echo ' <small>(+' . (count($names) - 2) . ')</small>';
                }
            } else {
                echo '—';
            }
            break;
            
        case 'profili':
            $terms = get_the_terms($post_id, 'profili_professionali');
            if ($terms && !is_wp_error($terms)) {
                $names = wp_list_pluck($terms, 'name');
                echo esc_html(implode(', ', array_slice($names, 0, 2)));
                if (count($names) > 2) {
                    echo ' <small>(+' . (count($names) - 2) . ')</small>';
                }
            } else {
                echo '—';
            }
            break;
            
        case 'aree_competenza':
            $terms = get_the_terms($post_id, 'aree_di_competenza');
            if ($terms && !is_wp_error($terms)) {
                echo esc_html($terms[0]->name);
                if (count($terms) > 1) {
                    echo ' <small>(+' . (count($terms) - 1) . ')</small>';
                }
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_protocollo_posts_custom_column', 'meridiana_fill_admin_columns', 10, 2);
add_action('manage_modulo_posts_custom_column', 'meridiana_fill_admin_columns', 10, 2);

/* ========================================
   NOTIFICHE E ALERT
   ======================================== */

/**
 * Notifica quando mancano PDF nei documenti
 */
function meridiana_check_missing_pdfs() {
    global $post_type, $post;
    
    if (!in_array($post_type, array('protocollo', 'modulo')) || !is_admin()) {
        return;
    }
    
    // Solo nella pagina di modifica post
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($post->ID)) {
        $pdf_field = ($post_type === 'protocollo') ? 'file_pdf_del_protocollo' : 'file_pdf_del_modulo';
        $pdf_file = get_field($pdf_field, $post->ID);
        
        if (empty($pdf_file)) {
            add_action('admin_notices', function() use ($post_type) {
                echo '<div class="notice notice-warning">';
                echo '<p><strong>⚠️ Attenzione:</strong> Questo ' . $post_type . ' non ha un file PDF associato. ';
                echo 'I dipendenti non potranno visualizzare il documento.</p>';
                echo '</div>';
            });
        }
    }
}
add_action('admin_init', 'meridiana_check_missing_pdfs');

/**
 * Footer admin con info versione child theme
 */
function meridiana_admin_footer_text($text) {
    global $post_type;
    
    if (in_array($post_type, array('protocollo', 'modulo', 'convenzione', 'contatto', 'salute'))) {
        $text .= ' | <span style="color: #AB1120;">Child Theme La Meridiana v' . MERIDIANA_CHILD_VERSION . '</span>';
    }
    
    return $text;
}
add_filter('admin_footer_text', 'meridiana_admin_footer_text');

/**
 * Fine modulo admin enhancements
 */