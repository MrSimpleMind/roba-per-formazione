<?php
/**
 * Template Single Protocollo - La Meridiana
 * Visualizza protocolli con PDF embeddato (solo visualizzazione)
 * 
 * Compatible con pagina ricerca Divi
 */

get_header(); ?>

<div class="meridiana-protocollo-container">
    <?php while (have_posts()) : the_post(); ?>
        
        <article class="protocollo-single" id="protocollo-<?php the_ID(); ?>">
            
            <!-- Breadcrumb di ritorno alla ricerca -->
            <nav class="protocollo-breadcrumb">
                <a href="<?php echo home_url('/ricerca-protocolli/'); ?>" class="btn-back">
                    ← Torna alla ricerca protocolli
                </a>
            </nav>

            <!-- Header Protocollo -->
            <header class="protocollo-header">
                <h1 class="protocollo-title"><?php the_title(); ?></h1>
                
                <!-- Meta info con taxonomies -->
                <div class="protocollo-meta-container">
                    <?php 
                    $unita_offerta = get_the_terms(get_the_ID(), 'unita_di_offerta');
                    $profili = get_the_terms(get_the_ID(), 'profili_professionali');
                    ?>
                    
                    <?php if ($unita_offerta && !is_wp_error($unita_offerta)): ?>
                        <div class="protocollo-meta">
                            <span class="meta-label">Unità di Offerta:</span>
                            <span class="meta-value">
                                <?php echo implode(', ', wp_list_pluck($unita_offerta, 'name')); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($profili && !is_wp_error($profili)): ?>
                        <div class="protocollo-meta">
                            <span class="meta-label">Profili Professionali:</span>
                            <span class="meta-value">
                                <?php echo implode(', ', wp_list_pluck($profili, 'name')); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Data pubblicazione -->
                    <div class="protocollo-meta">
                        <span class="meta-label">Pubblicato:</span>
                        <span class="meta-value"><?php echo get_the_date('d/m/Y'); ?></span>
                    </div>
                </div>
            </header>

            <!-- Riassunto del protocollo -->
            <?php 
            $riassunto = get_field('riassunto');
            if ($riassunto): ?>
                <section class="protocollo-riassunto">
                    <h3>Descrizione</h3>
                    <div class="riassunto-content">
                        <?php echo wp_kses_post($riassunto); ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- PDF Embed Section -->
            <?php 
            $pdf_file = get_field('file_pdf_del_protocollo');
            if ($pdf_file): 
                // Tracking visualizzazione (usando funzione child theme)
                if (function_exists('meridiana_track_document_view')) {
                    meridiana_track_document_view(get_the_ID(), get_current_user_id());
                }
            ?>
                <section class="protocollo-pdf-section">
                    <div class="pdf-header">
                        <h3>Documento Protocollo</h3>
                    </div>
                    
                    <div class="pdf-embed-container">
                        <?php 
                        // PDF Embedder con parametri specifici per protocolli
                        echo do_shortcode('[pdf-embedder 
                            url="' . esc_url($pdf_file) . '" 
                            download="off" 
                            toolbar="bottom" 
                            width="max" 
                            height="800"
                            mobilewidth="300"
                            toolbarfixed="on"
                            title="' . esc_attr(get_the_title()) . '"
                        ]'); 
                        ?>
                    </div>
                </section>
            <?php else: ?>
                <section class="protocollo-no-pdf">
                    <div class="no-pdf-message">
                        <h3>⚠️ Documento non disponibile</h3>
                        <p>Il documento PDF per questo protocollo non è ancora stato caricato.</p>
                        <p>Contatta l'amministratore per ulteriori informazioni.</p>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Moduli Associati - SEZIONE CONDIZIONALE -->
            <?php 
            $moduli_associati = get_field('moduli_associati'); 
            if ($moduli_associati && is_array($moduli_associati) && count($moduli_associati) > 0): ?>
                <section class="protocollo-moduli-associati">
                    <h3>Moduli Correlati</h3>
                    <p class="moduli-intro">Scarica i moduli necessari per questo protocollo:</p>
                    
                    <div class="moduli-grid">
                        <?php foreach ($moduli_associati as $modulo): 
                            $modulo_id = is_object($modulo) ? $modulo->ID : $modulo;
                            $modulo_title = get_the_title($modulo_id);
                            $modulo_pdf = get_field('file_pdf_del_modulo', $modulo_id);
                            $modulo_excerpt = get_the_excerpt($modulo_id);
                            
                            // Get taxonomies for the modulo
                            $modulo_aree = get_the_terms($modulo_id, 'aree_di_competenza');
                            $modulo_udo = get_the_terms($modulo_id, 'unita_di_offerta');
                        ?>
                            <div class="modulo-card">
                                <div class="modulo-header">
                                    <h4 class="modulo-name"><?php echo esc_html($modulo_title); ?></h4>
                                    
                                    <!-- Meta info modulo -->
                                    <div class="modulo-meta">
                                        <?php if ($modulo_aree && !is_wp_error($modulo_aree)): ?>
                                            <span class="modulo-area">
                                                📋 <?php echo esc_html($modulo_aree[0]->name); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($modulo_udo && !is_wp_error($modulo_udo)): ?>
                                            <span class="modulo-udo">
                                                🏢 <?php echo esc_html(implode(', ', wp_list_pluck(array_slice($modulo_udo, 0, 2), 'name'))); ?>
                                                <?php if (count($modulo_udo) > 2): ?>
                                                    <span class="udo-more">+<?php echo count($modulo_udo) - 2; ?></span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Descrizione breve se disponibile -->
                                <?php if ($modulo_excerpt): ?>
                                    <div class="modulo-description">
                                        <?php echo wp_trim_words($modulo_excerpt, 15, '...'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Azioni download -->
                                <div class="modulo-actions">
                                    <?php if ($modulo_pdf): ?>
                                        <a href="<?php echo esc_url($modulo_pdf); ?>" 
                                           class="btn-download-modulo" 
                                           target="_blank"
                                           download
                                           onclick="console.log('Download modulo:', '<?php echo esc_js($modulo_title); ?>');">
                                            📥 Scarica PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="btn-no-download">
                                            ⚠️ PDF non disponibile
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Link alla pagina del modulo (opzionale) -->
                                    <a href="<?php echo get_permalink($modulo_id); ?>" 
                                       class="btn-view-modulo"
                                       target="_blank">
                                        👁️ Dettagli
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Info footer sezione moduli -->
                    <div class="moduli-footer-info">
                        <p><small>💡 <strong>Suggerimento:</strong> I moduli si apriranno in una nuova finestra per facilitare la compilazione.</small></p>
                    </div>
                </section>
            <?php endif; ?>

        </article>

    <?php endwhile; ?>
</div>

<!-- CSS Ottimizzato per La Meridiana Design System -->
<style>
/* Container principale */
.meridiana-protocollo-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 15px;
    padding-bottom: 80px; /* Spazio per menu persistente bottom */
    font-family: Arial, sans-serif;
    background-color: #F9FAFC;
}

/* Breadcrumb */
.protocollo-breadcrumb {
    margin-bottom: 15px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: #F9FAFC;
    border: 1px solid #EAEAEA;
    border-radius: 10px;
    text-decoration: none;
    color: #5A5A5A;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-back:hover {
    background: #EAEAEA;
    color: #AB1120;
}

/* Header protocollo - SEMPLIFICATO */
.protocollo-header {
    background: #F9FAFC;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #EAEAEA;
}

.protocollo-title {
    font-family: Arial, sans-serif;
    font-size: clamp(22px, 4vw, 32px); /* Responsive ma non troppo grande */
    font-weight: 700;
    color: #1C1C1C;
    margin: 0 0 15px 0;
    line-height: 1.3;
}

.protocollo-meta-container {
    display: grid;
    gap: 8px;
    margin-top: 15px;
}

.protocollo-meta {
    display: flex;
    align-items: center;
    background: #F9FAFC; /* Sfondo grigino mantenuto come richiesto */
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #EAEAEA;
}

.meta-label {
    font-weight: 600;
    color: #5A5A5A;
    margin-right: 8px;
    min-width: 120px;
    font-size: 14px;
}

.meta-value {
    color: #2D2D2D;
    font-size: 14px;
}

/* Riassunto - SEMPLIFICATO */
.protocollo-riassunto {
    background: #FFFFFF;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 10px;
}

.protocollo-riassunto h3 {
    color: #AB1120;
    margin-bottom: 12px;
    font-size: 20px;
    font-family: Arial, sans-serif;
}

.riassunto-content {
    line-height: 1.6;
    color: #2D2D2D;
    font-size: 16px;
}

/* PDF Section - PULITA E PROFESSIONALE */
.protocollo-pdf-section {
    background: #FFFFFF;
    border-radius: 10px;
    margin-bottom: 20px;
}

.pdf-header {
    background: #F9FAFC;
    padding: 15px 20px;
    border-bottom: 1px solid #EAEAEA;
    border-radius: 10px 10px 0 0;
}

.pdf-header h3 {
    margin: 0;
    color: #AB1120;
    font-size: 20px;
    font-family: Arial, sans-serif;
}

/* RIMOSSO: .pdf-notice (box giallo) */

.pdf-embed-container {
    padding: 20px;
    background: #FFFFFF;
}

/* No PDF message */
.protocollo-no-pdf {
    background: #FFFFFF;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    margin-bottom: 20px;
    border: 1px solid #EAEAEA;
}

.no-pdf-message h3 {
    color: #AB1120;
    margin-bottom: 15px;
    font-size: 20px;
}

/* Moduli associati - DESIGN AGGIORNATO */
.protocollo-moduli-associati {
    background: #FFFFFF;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #EAEAEA;
}

.protocollo-moduli-associati h3 {
    color: #AB1120;
    margin-bottom: 8px;
    font-size: 20px;
    font-family: Arial, sans-serif;
}

.moduli-intro {
    color: #5A5A5A;
    font-size: 14px;
    margin-bottom: 20px;
    font-style: italic;
}

/* Grid moduli */
.moduli-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

/* Card singolo modulo */
.modulo-card {
    background: #F9FAFC;
    border: 1px solid #EAEAEA;
    border-radius: 8px;
    padding: 16px;
    transition: all 0.2s ease;
}

.modulo-card:hover {
    border-color: #AB1120;
    box-shadow: 0 2px 8px rgba(171, 17, 32, 0.1);
}

.modulo-header {
    margin-bottom: 12px;
}

.modulo-name {
    color: #1C1C1C;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px 0;
    line-height: 1.3;
}

.modulo-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 8px;
}

.modulo-area,
.modulo-udo {
    font-size: 12px;
    color: #5A5A5A;
    display: flex;
    align-items: center;
    gap: 4px;
}

.udo-more {
    background: #AB1120;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    margin-left: 4px;
}

.modulo-description {
    color: #2D2D2D;
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 12px;
    opacity: 0.8;
}

/* Azioni modulo */
.modulo-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-download-modulo {
    background: #AB1120;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: background 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-download-modulo:hover {
    background: #8A0E1A;
    color: white;
}

.btn-view-modulo {
    background: transparent;
    color: #5A5A5A;
    padding: 6px 12px;
    border: 1px solid #EAEAEA;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-view-modulo:hover {
    border-color: #AB1120;
    color: #AB1120;
}

.btn-no-download {
    color: #5A5A5A;
    font-size: 12px;
    padding: 6px 12px;
    background: #F9FAFC;
    border-radius: 6px;
    border: 1px solid #EAEAEA;
}

/* Footer info sezione */
.moduli-footer-info {
    border-top: 1px solid #EAEAEA;
    padding-top: 15px;
    margin-top: 15px;
}

.moduli-footer-info p {
    margin: 0;
    color: #5A5A5A;
}

.moduli-footer-info small {
    font-size: 13px;
}

/* RIMOSSO: .protocollo-actions (pulsanti footer) */

/* Mobile responsive - OTTIMIZZATO */
@media (max-width: 768px) {
    .meridiana-protocollo-container {
        padding: 12px;
        padding-bottom: 100px; /* Più spazio per menu mobile */
    }
    
    .protocollo-header {
        padding: 15px;
    }
    
    .protocollo-title {
        font-size: clamp(20px, 5vw, 26px); /* Ridotto per mobile */
    }
    
    .protocollo-riassunto,
    .pdf-header,
    .pdf-embed-container,
    .protocollo-moduli-associati {
        padding: 15px;
    }
    
    .protocollo-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .meta-label {
        margin-bottom: 0;
        min-width: auto;
        font-size: 13px;
    }
    
    .meta-value {
        font-size: 13px;
    }
    
    .riassunto-content {
        font-size: 15px;
    }
    
    .protocollo-riassunto h3,
    .pdf-header h3,
    .protocollo-moduli-associati h3 {
        font-size: 18px;
    }
    
    /* Moduli grid responsive */
    .moduli-grid {
        grid-template-columns: 1fr; /* Single column su mobile */
        gap: 12px;
    }
    
    .modulo-card {
        padding: 12px;
    }
    
    .modulo-name {
        font-size: 15px;
    }
    
    .modulo-actions {
        flex-direction: column;
        gap: 8px;
        align-items: stretch;
    }
    
    .btn-download-modulo,
    .btn-view-modulo {
        text-align: center;
        justify-content: center;
    }
}

/* Tablet responsive */
@media (max-width: 1024px) and (min-width: 769px) {
    .meridiana-protocollo-container {
        padding: 20px;
        padding-bottom: 90px;
    }
    
    .protocollo-title {
        font-size: clamp(24px, 4vw, 28px);
    }
}

/* Print styles */
@media print {
    .protocollo-breadcrumb {
        display: none !important;
    }
    
    .protocollo-header {
        background: white !important;
        color: black !important;
        border: 2px solid #AB1120;
    }
    
    .protocollo-title {
        color: #AB1120 !important;
    }
    
    .meridiana-protocollo-container {
        padding-bottom: 20px !important;
    }
}

/* Miglioramenti tipografici */
h1, h2, h3, h4, h5, h6 {
    font-family: Arial, sans-serif;
}

p, div, span {
    font-family: Arial, sans-serif;
}

/* Focus accessibility */
.btn-back:focus,
.btn-download-modulo:focus {
    outline: 2px solid #AB1120;
    outline-offset: 2px;
}
</style>

<!-- JavaScript semplificato -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tracking tempo di permanenza (opzionale)
    let startTime = Date.now();
    
    window.addEventListener('beforeunload', function() {
        let timeSpent = Math.round((Date.now() - startTime) / 1000);
        
        // Tracking tempo di lettura se superiore a 10 secondi
        if (timeSpent > 10) {
            // Eventualmente: invia dati via AJAX per analytics
            console.log('Tempo lettura protocollo:', timeSpent + ' secondi');
        }
    });
    
    // Smooth scroll per PDF
    const pdfContainer = document.querySelector('.pdf-embed-container');
    if (pdfContainer) {
        // Scroll verso PDF dopo un piccolo delay per il caricamento
        setTimeout(function() {
            pdfContainer.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start',
                inline: 'nearest'
            });
        }, 500);
    }
});
</script>

<?php get_footer(); ?>
