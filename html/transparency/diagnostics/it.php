<?php
/**
 * Public Transparency: Opt-in Diagnostics & Phantom Wing
 *
 * PURPOSE:
 * Explain how PayCal's optional diagnostics system works, what data it collects
 * (and what it never collects), who controls it, and how it helps troubleshoot
 * problems without compromising privacy.
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
$pageTitle = 'Diagnostica opt-in e Phantom Wing - [PayCal]';
$pageLabel = 'Diagnostica opt-in e Phantom Wing';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Diagnostica opt-in &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1>Diagnostica opt-in &amp; Phantom Wing</h1>
    <p class="deck">
      PayCal include un livello di diagnostica opzionale che voi controllate. Ecco esattamente
      cosa raccoglie, cosa rimane sul vostro dispositivo e come viene utilizzata.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Panoramica</h2>
      <p>
        PayCal viene fornito con un livello di diagnostica integrato chiamato <strong>Phantom Wing</strong>.
        Per impostazione predefinita è quasi completamente silenzioso — cattura solo errori gravi
        non gestiti e non invia mai nulla senza la vostra esplicita attivazione.
      </p>
      <p>
        Se riscontrate un problema e volete condividere più contesto con il supporto, potete
        abilitare diagnostica aggiuntiva in
        <a href="/settings/">Impostazioni → Debug (Opzionale)</a>.
        Ogni impostazione è indipendente; potete attivare solo quella rilevante.
        Tutte e tre sono <strong>Disattivate</strong> per impostazione predefinita.
      </p>
    </section>

    <section class="doc-section">
      <h2>I tre controlli opt-in</h2>
      <p>
        Ogni controllo si trova nel pannello <strong>Debug (Opzionale)</strong> in fondo alla
        vostra pagina Impostazioni. Sono progettati solo per la risoluzione dei problemi —
        attivarli può rallentare leggermente le interazioni di pagina perché viene svolto
        lavoro aggiuntivo nel browser.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Impostazione</th>
            <th>Cosa abilita</th>
            <th>Chi la vede</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Messaggi di console</strong></td>
            <td>
              Emette avvisi, log informativi e marcatori di prestazioni nella console per
              sviluppatori del browser. Utile per l'autodiagnosi — aprite i DevTools e cercate
              i messaggi con il prefisso <code>[PayCal]</code> o marcatori emoji.
            </td>
            <td>Solo voi (la vostra console del browser, mai trasmesso)</td>
          </tr>
          <tr>
            <td><strong>Diagnostica dettagliata</strong></td>
            <td>
              Abilita la registrazione interna passo-passo degli eventi. Phantom Wing cattura
              il ciclo di vita completo delle operazioni (caricamenti calendario, invii moduli,
              eventi di sessione) in un log in memoria incluso in qualsiasi rapporto di supporto
              che scegliete di condividere.
            </td>
            <td>Solo voi, a meno che non condividiate un rapporto di supporto</td>
          </tr>
          <tr>
            <td><strong>Informazioni di rete</strong></td>
            <td>
              Registra i tempi delle richieste API — quanto impiega ogni andata e ritorno al
              server, le dimensioni delle risposte e se è stato applicato il batching o il
              caching. Aiuta a diagnosticare la lentezza su operazioni specifiche.
            </td>
            <td>Solo voi (la vostra console del browser, mai trasmesso)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Cosa fa Phantom Wing per impostazione predefinita</h2>
      <p>
        Anche con tutti e tre i controlli disattivati, Phantom Wing esegue un monitor di base
        leggero che cattura solo i guasti gravi:
      </p>
      <ul class="doc-list">
        <li>Eccezioni JavaScript non catturate (<code>window.onerror</code>)</li>
        <li>Rifiuti di promesse non gestiti</li>
        <li>Chiamate Fetch che falliscono con un errore di rete (non errori HTTP — questi vengono gestiti per funzionalità)</li>
      </ul>
      <p>
        Questi dati di base rimangono interamente in memoria e non vengono mai trasmessi da
        nessuna parte. Vengono visualizzati in un riepilogo di un secondo nella console del
        browser al caricamento della pagina in modo da poter vedere rapidamente se qualcosa
        è andato storto, poi vengono eliminati.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Baseline output when all clear (console, diagnostics off):
[PHANTOM WING] All clear - no errors or warnings detected.

// Baseline output when issues exist:
[PHANTOM WING] Error Summary
Total issues: 2 across 2 grouped location(s).
WARN 1: FormSubmit timed out after 8000ms
ERROR 1: Uncaught TypeError in calendar renderer</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Phantom Wing &amp; Telemetria</h2>
      <p>
        Phantom Wing dispone di un canale di telemetria leggero utilizzato per misurare
        l'affidabilità delle funzionalità in modo aggregato — ad esempio, rilevare se una
        particolare operazione sta fallendo a un tasso insolito su tutta la piattaforma.
      </p>
      <h3>Cosa invia la telemetria</h3>
      <ul class="doc-list">
        <li>Conteggi di eventi anonimizzati raggruppati per ora (es. <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Categoria e tipo di errore — mai il messaggio di errore completo o lo stack trace</li>
        <li>Nessun identificatore utente, nessun token di sessione, nessun indirizzo IP</li>
      </ul>
      <h3>Cosa non invia mai la telemetria</h3>
      <ul class="doc-list">
        <li>Il vostro nome, e-mail o qualsiasi dettaglio dell'account</li>
        <li>Guadagni, periodo di paga o dati finanziari</li>
        <li>Messaggi di errore completi o stack trace</li>
        <li>Percorsi URL o stringhe di query</li>
        <li>Pressioni di tasti o valori dei campi modulo</li>
      </ul>
      <h3>Limitazione della velocità &amp; back-off</h3>
      <p>
        Le trasmissioni di telemetria sono limitate lato server per utente al minuto. Se il
        vostro client supera la soglia, il server conferma silenziosamente e scarta l'eccesso —
        nulla viene memorizzato. Il client applica anche il back-off esponenziale: dopo due
        fallimenti consecutivi lato server disabilita automaticamente la trasmissione di
        telemetria per dieci minuti.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Telemetry payload shape (no personal data):
{
  "type": "pw.performance.metrics",
  "fields": {
    "count": 1,
    "bucket_hour": 2026030914,
    "flush_reason": "timer"
  }
}</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Oscuramento dei dati</h2>
      <p>
        Prima che un valore venga memorizzato in memoria o trasmesso tramite telemetria,
        Phantom Wing applica un passaggio di oscuramento automatico. I valori che corrispondono
        a pattern sensibili noti vengono sostituiti con <code>[REDACTED]</code>:
      </p>
      <ul class="doc-list">
        <li>Indirizzi e-mail</li>
        <li>Token Bearer e valori dell'intestazione di autorizzazione</li>
        <li>Token CSRF</li>
        <li>Stringhe che sembrano chiavi crittografiche o blob codificati in base64 sopra una lunghezza minima</li>
      </ul>
      <p>
        L'oscuramento opera su tutti gli argomenti passati ai metodi console intercettati e su
        tutti i valori dei campi di telemetria prima dell'accodamento. Non può essere aggirato
        abilitando le impostazioni di diagnostica.
      </p>
    </section>

    <section class="doc-section">
      <h2>Guardie di ambito: pagine in cui la diagnostica è soppressa</h2>
      <p>
        La trasmissione di telemetria è completamente soppressa sulle pagine di autenticazione
        (<code>/auth/</code>). Ciò significa che anche se le Informazioni di rete sono attivate,
        nessuna telemetria viene trasmessa mentre vi trovate nei flussi di accesso, registrazione
        o recupero. Si tratta di una misura di difesa in profondità per prevenire qualsiasi
        possibilità che dati adiacenti alle credenziali appaiano nei canali di diagnostica.
      </p>
    </section>

    <section class="doc-section">
      <h2>Il vostro controllo</h2>
      <p>
        Tutte e tre le impostazioni di diagnostica sono memorizzate come preferenze dell'account,
        non come cookie del browser. Seguono il vostro account su tutti i dispositivi e le sessioni
        e sono <strong>Disattivate</strong> per impostazione predefinita per ogni account —
        inclusi i nuovi. Potete modificarle in qualsiasi momento in
        <a href="/settings/">Impostazioni → Debug (Opzionale)</a>.
      </p>
      <p>
        La disattivazione di un'impostazione ha effetto immediato al successivo caricamento della
        pagina. Nessun dato diagnostico viene conservato tra le sessioni: il log in memoria di
        Phantom Wing viene cancellato quando navigate altrove o chiudete la scheda.
      </p>
    </section>

    <section class="doc-section">
      <h2>Riepilogo</h2>
      <ol class="doc-list">
        <li>Tutti e tre i controlli di debug sono <strong>Disattivati</strong> per impostazione predefinita e devono essere abilitati esplicitamente da voi</li>
        <li>Messaggi di console e Informazioni di rete non lasciano mai il vostro dispositivo</li>
        <li>La Diagnostica dettagliata rimane in memoria e viene condivisa solo se scegliete di condividere un rapporto di supporto</li>
        <li>La telemetria invia solo conteggi di eventi anonimizzati e aggregati — zero dati personali</li>
        <li>Tutti i valori vengono oscurati prima della memorizzazione o trasmissione, indipendentemente dalle impostazioni di diagnostica</li>
        <li>La telemetria è completamente soppressa su tutte le pagine di autenticazione</li>
        <li>La limitazione della velocità e il back-off automatico del client prevengono qualsiasi segnalazione eccessiva accidentale</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Phantom Wing è progettato in modo da poter lasciare tutta la diagnostica disattivata
        indefinitamente. I controlli opt-in esistono per dare a voi e al team di supporto un
        linguaggio condiviso quando qualcosa va storto — non per raccogliere dati per impostazione
        predefinita.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
