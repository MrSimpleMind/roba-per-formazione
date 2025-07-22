# 📊 Tracker Progetto - Piattaforma Formazione La Meridiana

## ✅ **COMPLETATO**

### **Fase 1: Setup Base**
- [x] **Hosting e ambiente**
  - Setup WPmuDEV hosting ✅
  - Installazione WordPress + DIVI 5 ✅
  - Configurazione SSL e sicurezza base ✅

- [x] **Plugin Core installati**
  - ACF Pro ✅
  - LearnDash LMS ✅
  - WPmuDEV Suite (Defender Pro, Hummingbird Pro, Smush Pro) ✅

### **Fase 2: Custom Post Types e Campi**

#### **✅ CPT Protocolli**
**Gruppo campi ACF**: "Campi per i protocolli"
- Campo: **File PDF del protocollo** 
  - Nome: `file_pdf_del_protocollo`
  - Tipo: File
  - Obbligatorio: ✅
- Campo: **Riassunto**
  - Nome: `riassunto`  
  - Tipo: Area di testo

**Regole di posizionamento**: Post type = Protocollo

#### **✅ CPT Moduli**
**Gruppo campi ACF**: "Campi per i moduli"
- Campo: **File PDF del modulo**
  - Nome: `file_pdf_del_modulo`
  - Tipo: File

**Regole di posizionamento**: Post type = Modulo
**Shortcode generato**: `[frontend_admin group=684a4a29a61cf edit=false]`

#### **✅ Campi Utente**
**Gruppo campi ACF**: "Campi per utenti"
- Campo: **Codice fiscale**
  - Nome: `codice_fiscale`
  - Tipo: Testo
- Campo: **UDO Primaria**
  - Nome: `udo_primaria`
  - Tipo: Checkbox
- Campo: **UDO Secondaria**
  - Nome: `udo_secondaria`
  - Tipo: Checkbox
- Campo: **Stato del lavoratore**
  - Nome: `stato_del_lavoratore`
  - Tipo: Selezione
- Campo: **URL Formazione Esterna**
  - Nome: `url_formazione_esterna`
  - Tipo: URL

**Regole di posizionamento**: Modulo utente = Aggiungi

#### **✅ Campi Articoli (News con Call to Action)**
**Gruppo campi ACF**: "Campi per gli articoli"
- Campo: **Call to action**
  - Nome: `call_to_action`
  - Tipo: Vero / Falso
- Campo: **Elementi della call to action**
  - Nome: `elementi_della_call_to_action`
  - Tipo: Checkbox
- Campo: **Titolo call to action**
  - Nome: `titolo_call_to_action`
  - Tipo: Testo
- Campo: **Descrizione call to action**
  - Nome: `descrizione_call_to_action`
  - Tipo: Area di testo
- Campo: **Testo del pulsante call to action**
  - Nome: `testo_del_pulsante_call_to_action`
  - Tipo: Testo
- Campo: **Link del pulsante call to action**
  - Nome: `link_del_pulsante_call_to_action`
  - Tipo: URL

**Regole di posizionamento**: Post type = Articolo

#### **✅ Child Theme e Ottimizzazioni**
**Child Theme implementato**: "Divi Child - La Meridiana"
- ✅ Rimozione completa sistema commenti
- ✅ Rimozione CPT "Projects" Divi
- ✅ Disabilitazione emoji (-15KB)
- ✅ Disabilitazione XML-RPC
- ✅ Cleanup meta tags
- ✅ Rimozione versioni CSS/JS

**Funzioni preparatorie aggiunte**:
- `meridiana_track_document_view()` - Per tracking PDF
- `meridiana_can_user_download()` - Per controllo permessi download
- `meridiana_cleanup_database()` - Per pulizia periodica

**Performance stimata**: ~25KB ridotti + meno query database

---

#### **✅ CPT Contatti (Organigramma)**
**Gruppo campi ACF**: "Campi per contatti"
- Campo: **Ruolo**
  - Nome: `ruolo`
  - Tipo: Testo
- Campo: **Email aziendale**
  - Nome: `email_aziendale`
  - Tipo: Email
- Campo: **Interno**
  - Nome: `interno`
  - Tipo: Numero
- Campo: **Cellulare aziendale**
  - Nome: `cellulare_aziendale`
  - Tipo: Testo

**Regole di posizionamento**: Post type = Contatto

#### **✅ CPT Salute (Salute e Benessere)**
**Gruppo campi ACF**: "Campo per salute"
- Campo: **Volantini/file eventuali**
  - Nome: `risorse_file`
  - Tipo: File
- Campo: **Link a risorse esterne**
  - Nome: `risorse_url`
  - Tipo: URL

**Regole di posizionamento**: Post type = Salute

#### **✅ CPT Convenzioni**
**Gruppo campi ACF**: "Campi per Convenzioni"
- Campo: **Convenzione attiva**
  - Nome: `convenzione_attiva`
  - Tipo: Vero / Falso

**Regole di posizionamento**: Post type = Convenzione

### **✅ Taxonomies Condivise Create e Associate**
- [x] **Unità di Offerta** (10 termini)
  - ✅ Associata a: Moduli, Protocolli
- [x] **Profili Professionali** (14 termini)
  - ✅ Associata a: Protocolli, Moduli
- [x] **Aree di Competenza** (7 termini)
  - ✅ Associata a: Moduli

---

## 🔄 **IN CORSO**

### **Fase 2: Gestione Contenuti - INIZIAMO!**
- [ ] **PDF Embedder Premium**
  - Installazione PDF Embedder Premium
  - ✅ Template single-protocollo.php custom completato
  - ✅ Design system aligned (colori, font, spaziature La Meridiana)
  - ✅ Mobile responsive ottimizzato
  - ✅ Interfaccia pulita e professionale
  - ✅ Sezione "Moduli Associati" con campo relazione ACF
  - ✅ Download diretto moduli correlati
  - Configurazione parametri visualizzazione vs download
- [ ] **PDF Management**
  - Installazione PDF Embedder Premium
  - Configurazione embed per protocolli (no download)
  - Download permesso per moduli
- [ ] **Frontend Admin**
  - Installazione Frontend Admin by DinamiPress
  - Form frontend per Gestori Piattaforma
  - Workflow di pubblicazione

### **Fase 3: Sistema Ruoli**
- [ ] **Ruoli custom WordPress**
  - Admin (già presente)
  - Gestore Piattaforma
  - Dipendente
- [ ] **Permissions mapping**

### **Fase 4: LearnDash Setup**
- [ ] **Configurazione Corsi**
  - Creazione categorie: Obbligatori Interni, Obbligatori Esterni, Facoltativi
  - Setup auto-enrollment per dipendenti
  - Configurazione scadenze per tipo corso
- [ ] **Certificati e Tracking**

### **Fase 5: Sistema Analytics Custom**
- [ ] **Database Custom**
  - Tabella `wp_document_views`
  - Tracking visualizzazioni
- [ ] **Dashboard Analytics**
  - Interfaccia per Admin/Gestori
  - Report visualizzazioni
  - Export funzionalità

### **Fase 6: Notifiche & PWA**
- [ ] **BREVO Integration**
  - Plugin WordPress BREVO
  - Configurazione Web Push
  - Setup eventi automatici
- [ ] **PWA Setup**
  - Super PWA plugin
  - Service worker personalizzato

### **Fase 7: Automazioni**
- [ ] **Cleanup Automatico**
  - Hook per pulizia vecchi PDF
- [ ] **BREVO API Sync**
  - Sync nuovi utenti

### **Fase 8: Sicurezza e Testing**
- [ ] **Two-Factor Authentication**
- [ ] **Audit Log**
- [ ] **GDPR Compliance**
- [ ] **Performance Testing**
- [ ] **Security Testing**

---

## 🎯 **Note per lo Sviluppo**

### **ID Campi ACF Mappati**
```php
// Protocolli
'file_pdf_del_protocollo'  // Campo File obbligatorio
'riassunto'                // Campo Area di testo

// Moduli  
'file_pdf_del_modulo'      // Campo File

// Convenzioni
'convenzione_attiva'       // Campo Vero/Falso

// Contatti (Organigramma)
'ruolo'                    // Campo Testo
'email_aziendale'          // Campo Email
'interno'                  // Campo Numero
'cellulare_aziendale'      // Campo Testo

// Salute (Salute e Benessere)
'risorse_file'             // Campo File
'risorse_url'              // Campo URL

// Utenti
'codice_fiscale'           // Campo Testo
'udo_primaria'             // Campo Checkbox
'udo_secondaria'           // Campo Checkbox  
'stato_del_lavoratore'     // Campo Selezione
'url_formazione_esterna'   // Campo URL

// Articoli (News)
'call_to_action'                      // Campo Vero/Falso
'elementi_della_call_to_action'       // Campo Checkbox
'titolo_call_to_action'               // Campo Testo
'descrizione_call_to_action'          // Campo Area di testo
'testo_del_pulsante_call_to_action'   // Campo Testo
'link_del_pulsante_call_to_action'    // Campo URL
```

### **Shortcode Disponibili**
```
[frontend_admin group=684a4a29a61cf edit=false]  // Moduli frontend
```

---

## 📈 **Progresso Generale**

**Completamento stimato**: 65% ✅ 

### **🎯 MILESTONE RAGGIUNTO: Struttura Dati Completa! 🎉**

- ✅ Setup base completato
- ✅ **TUTTI i 5 CPT creati con campi** (Protocolli, Moduli, Convenzioni, Contatti, Salute)
- ✅ **Tutte le taxonomies condivise create e associate correttamente**
- ✅ Campi utente configurati  
- ✅ Sistema articoli con CTA pronto
- ✅ **Child Theme Divi implementato e attivato**
- ✅ **Ottimizzazioni performance applicate** (commenti rimossi, emoji disabilitati, etc.)
- 🚀 **Pronto per Fase 2: Gestione Contenuti**

**Prossimi step critici**:
1. ✅ ~~Completare tutti i CPT~~ → **COMPLETATO!**
2. ✅ ~~Creare e associare taxonomies~~ → **COMPLETATO!**
3. **🔥 Setup PDF Embedder** (differenza visualizzazione vs download)
4. **🔥 Configurazione Frontend Admin** per Gestori Piattaforma
5. **🔥 Sistema ruoli custom WordPress**

---

*Ultimo aggiornamento: 22 Luglio 2025 - ore 17:15 - 🎉 MILESTONE: Template Protocolli Completato + Design System Aligned!*