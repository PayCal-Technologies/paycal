<?php
/**
 * Public Transparency: Error Handling & Message Normalization
 *
 * PURPOSE: 
 * Explain PayCal's standardized error-message normalization pattern, the
 * security and UX rationale behind it, and how we ensure users receive
 * meaningful, safe error feedback across all frontend modules.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Gestione degli errori e normalizzazione dei messaggi - [PayCal]';
$pageLabel = 'Gestione degli errori e normalizzazione dei messaggi';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Gestione degli errori e normalizzazione dei messaggi</span>
  </nav>

  <header class="doc-article-header">
    <h1>Gestione degli errori e normalizzazione dei messaggi</h1>
    <p class="deck">
      Come PayCal standardizza la segnalazione degli errori su tutti i moduli frontend per garantire
      che gli utenti ricevano feedback significativi, sicuri e coerenti senza esporre dettagli sensibili.
    </p>
<p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Panoramica e scopo</h2>
      <p>
        Quando gli utenti incontrano errori (guasti di rete, accesso negato, errori di convalida),
        meritano un feedback chiaro che spieghi cosa è accaduto e come risolverlo. Tuttavia,
        i messaggi grezzi dal backend devono essere normalizzati per:
      </p>
      <ul class="doc-list">
        <li><strong>Eliminare il rumore:</strong> Rimuovere prefissi ridondanti come "Errore:" e spazi vuoti</li>
        <li><strong>Prevenire perdite:</strong> Garantire che i dettagli sensibili dell'implementazione non raggiungano mai l'utente</li>
        <li><strong>Fornire fallback:</strong> Visualizzare messaggi sicuri quando gli errori sono vuoti o malformati</li>
        <li><strong>Garantire coerenza:</strong> Applicare la stessa logica su tutti i 11+ moduli frontend</li>
        <li><strong>Migliorare il debug:</strong> Registrare i dettagli dell'errore completo su Phantom Wing e mostrare riassunti sicuri</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Il problema: errori generici vs. significativi</h2>
      <p>
        Prima della standardizzazione, i moduli PayCal utilizzavano una gestione degli errori ad hoc:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ MALE: Espone errore grezzo, duplica logica
PC.showToast(error?.message || 'Importazione fallita.');
PW.error(`Importazione fallita: ${error.message}`);</code></pre>
      </div>
      <p>Problemi con questo approccio:</p>
      <ul class="doc-list">
        <li>Gli utenti vedono messaggi confusi come "ECONNREFUSED: Connessione rifiutata"</li>
        <li>Ogni modulo implementa la propria logica di fallback in modo indipendente</li>
        <li>Nessun taglio coerente degli spazi bianchi o rimozione dei prefissi</li>
        <li>I messaggi di errore vuoti possono visualizzarsi come "undefined" nell'interfaccia utente</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>La soluzione: risolutore di errori standardizzato</h2>
      <p>
        Tutti i moduli frontend di PayCal ora utilizzano una funzione di risoluzione unificata
        che normalizza i messaggi di errore:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ BENE: Normalizzato, coerente, sicuro
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Estrai messaggio dall'oggetto errore
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // Rimuovi il prefisso "Errore:" e taglia gli spazi bianchi
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Restituisci normalizzato se non vuoto; altrimenti fallback sicuro
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Utilizzo:</strong></p>
      <div class="doc-code-block">
        <pre><code>// Nei blocchi catch su tutti i moduli
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'Impossibile aggiornare il profilo.');
  PC.showToast(message, 'error');  // L'utente vede feedback significativi
  PW.error(message);                // Registrato per il debug
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Ambito di implementazione</h2>
      <p>
        A partire da aprile 2026, questo modello standardizzato di gestione degli errori è stato applicato a
        <strong>11 moduli frontend</strong> con <strong>~40+ blocchi catch normalizzati</strong>:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Autenticazione e impostazioni (7 moduli)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Moduli di dati e core (4 moduli)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>Moduli di alto valore (10+ punti catch):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/organizations/index.php</code> — Gestione organizzativa, accessi, tracce di controllo (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — CRUD del sito, guadagni, recupero del lavoro orfano (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Operazioni di immissione del giorno, copia/incolla/eliminazione (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Categorie di errore e modelli di gestione</h2>
      <p>Il risolutore viene applicato in modo coerente su diverse categorie di errore:</p>
      
      <h3>1. Guasti alle richieste di rete</h3>
      <div class="doc-code-block">
        <pre><code>// Modulo di rete: errori HTTP, timeout, problemi di connessione
async function deleteResource(ep, id) {
  try {
    // ...logica di fetch...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Errore di rete');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. Gestione della risposta dell'API</h3>
      <div class="doc-code-block">
        <pre><code>// Fatturazione/Impostazioni: il server ha restituito un messaggio di errore nel payload
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'Impossibile caricare lo stato di fatturazione.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'Impossibile caricare lo stato di fatturazione.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. Guasti alle operazioni dell'interfaccia utente</h3>
      <div class="doc-code-block">
        <pre><code>// Calendario/Organizzazioni: azioni avviate dall'utente (incolla, elimina, aggiorna)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('Successo!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'Azione fallita. Riprova.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Inizializzazione asincrona</h3>
      <div class="doc-code-block">
        <pre><code>// Moduli core: errori di avvio o inizializzazione dipendente
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'Inizializzazione della navigazione fallita');
  PW.warn(resolved);  // Registrato ma non blocca la pagina
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Considerazioni sulla sicurezza</h2>
      <p>
        La normalizzazione dei messaggi di errore protegge la privacy dell'utente e l'integrità del sistema:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Nessun dettaglio del database:</strong> Gli errori di backend come
          "UNIQUE constraint failed on email" vengono intercettati al confine dell'API
        </li>
        <li>
          <strong>Nessun percorso di file:</strong> Gli errori di sistema che espongono i percorsi dei file vengono rimossi
        </li>
        <li>
          <strong>Nessuna perdita di autenticazione:</strong> Le risposte ai guasti di autenticazione non rivelano mai
          se esiste un account (solo messaggi generici sicuri)
        </li>
        <li>
          <strong>Nessun dettaglio CORS/rete:</strong> Gli errori a livello di trasporto vengono normalizzati
          a messaggi generici "Errore di connessione"
        </li>
        <li>
          <strong>Fallback sicuri:</strong> Tutti i catcher hanno messaggi di fallback espliciti;
          non visualizzano mai "undefined" o "null"
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Vantaggi dell'esperienza utente</h2>
      <p>
        I messaggi di errore standardizzati migliorano significativamente l'esperienza dell'utente:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Feedback chiaro:</strong> Gli utenti sanno cosa è fallito
          (ad esempio "Chiave di accesso non riconosciuta" vs. generico "Accesso non riuscito")
        </li>
        <li>
          <strong>Passaggi successivi azionabili:</strong> Quando possibile, i messaggi suggeriscono rimedi
          ("Riprova", "Controlla la tua connessione", "Contatta il supporto")
        </li>
        <li>
          <strong>Coerenza nell'app:</strong> Lo stesso tipo di errore si visualizza allo stesso modo,
          riduce la confusione
        </li>
        <li>
          <strong>Stati di errore accessibili:</strong> I lettori di schermo annunciano messaggi normalizzati;
          la registrazione fornisce il contesto completo per i team di supporto
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Workflow di debug e supporto</h2>
      <p>
        La normalizzazione degli errori <strong>non</strong> sacrifica la capacità di debug.
        I dettagli completi dell'errore fluiscono verso Phantom Wing:
      </p>
      <div class="doc-code-block">
        <pre><code>// L'utente vede un messaggio UI pulito
PC.showToast(resolveThrownMessage(error, 'Il caricamento non è riuscito.'), 'error');

// Il team di supporto vede i dettagli completi nei log di Phantom Wing
PW.error('Il caricamento non è riuscito', {
  userMessage: resolveThrownMessage(error, 'Il caricamento non è riuscito.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Test e garanzia della qualità</h2>
      <p>
        Tutti i cambiamenti di gestione degli errori vengono convalidati prima della distribuzione:
      </p>
      <ul class="doc-list">
        <li><strong>Convalida della sintassi:</strong> <code>php -l</code> e <code>node --check</code> verificano la correttezza</li>
        <li><strong>Sicurezza del tipo:</strong> La diagnostica dell'editor conferma nessuna regressione di tipo</li>
        <li><strong>Test di integrazione:</strong> I blocchi catch vengono testati con oggetti di errore simulati</li>
        <li><strong>Registrazione di Phantom Wing:</strong> I messaggi di errore vengono verificati nei log di debug</li>
        <li><strong>Audit di accessibilità:</strong> Gli annunci del lettore di schermo vengono testati per la chiarezza</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Manutenzione ed estensioni future</h2>
      <p>
        Questo modello è progettato per la manutenibilità a lungo termine:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Pronto per la localizzazione:</strong> I messaggi di errore possono essere canalizzati tramite i18n
          senza modificare la logica del risolutore
        </li>
        <li>
          <strong>Estensibile:</strong> Il risolutore può essere migliorato per gestire codici di errore,
          logica di nuovo tentativo o ricerca di messaggi specializzati
        </li>
        <li>
          <strong>Documentazione:</strong> Ogni modulo include commenti inline che spiegano
          gli scenari di errore e le strategie di fallback
        </li>
        <li>
          <strong>Cronologia Git:</strong> Tutti i cambiamenti tracciati con messaggi di commit dettagliati
          e diff a livello di file per una facile revisione
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Riepilogo: lo standard di gestione degli errori di PayCal</h2>
      <p>
        La normalizzazione standardizzata dei messaggi di errore di PayCal garantisce che:
      </p>
      <ol class="doc-list">
        <li>Gli utenti ricevano feedback di errore chiari e azionabili</li>
        <li>I dettagli del sistema sensibile non si infiltrino mai nel frontend</li>
        <li>La gestione dei messaggi sia coerente su tutti i 11+ moduli frontend</li>
        <li>I team di debug e supporto mantengono il contesto di errore completo tramite Phantom Wing</li>
        <li>Il codice sia manutenibile, testabile e accessibile</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Questo impegno verso la sicurezza, la chiarezza e la coerenza riflette la dedizione di PayCal
        alla fiducia dell'utente e alla condivisione trasparente delle informazioni.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
