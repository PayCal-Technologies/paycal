<?php
/**
 * Public Transparency Hub — Italiano
 *
 * PURPOSE: High-level philosophy and links to detailed transparency pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_link.php';

if (!function_exists('transparency_href')) {
  function transparency_href(string $path): string
  {
    return $path;
  }
}

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_HOME',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$readMoreLabel = 'Leggi di più';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Centro trasparenza - [PayCal]';
$pageLabel = 'Centro trasparenza';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">Centro trasparenza</span>
    </nav>

    <header class="doc-article-header">
      <h1>Centro trasparenza</h1>
      <p class="deck">Pubblichiamo il funzionamento di PayCal affinché gli utenti possano verificare le decisioni, non solo confidare nelle dichiarazioni.</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>Filosofia della piattaforma</h2>
        <p>PayCal è costruito attorno a operazioni ispezionabili: le formule sono documentate, i limiti di telemetria sono espliciti e la conservazione dei dati è finita per impostazione predefinita.</p>
        <p>Il nostro principio è semplice: se un sistema influisce sulla busta paga o sulla privacy, gli utenti devono poter capire come funziona e come viene governato.</p>
        <p>La fatturazione degli abbonamenti è gestita da Stripe. Il supporto Stripe è disponibile su <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a>.</p>
        <p>Gli aggiornamenti recenti che hanno plasmato il prodotto — inclusi i flussi di fatturazione e governance del profilo — sono tracciati nelle nostre pagine framework/backend e governance dei test.</p>
      </section>

      <div class="doc-panel-grid" aria-label="Pannelli di dettaglio trasparenza">
        <section class="doc-section">
          <h2>Stato dell'audit di sicurezza</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>Questa pagina pubblica lo stato corrente dell'audit, l'ambito chiuso, i riferimenti alle prove e gli impegni di blocco del rilascio che preservano la postura di sicurezza.</p>
          <ul class="doc-fact-list">
            <li>Lo stato del ciclo corrente è pubblicato con data di verifica e cadenza di revisione.</li>
            <li>La copertura dell'ambito include i controlli del ciclo di vita runtime, l'isolamento della telemetria, la governance della correlazione e il rafforzamento dei ruoli privilegiati.</li>
            <li>Lo snapshot di validazione include Playwright, JS, PHPStan livello 9 e i risultati dei test backend.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Metriche della piattaforma e privacy</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>La pagina delle metriche spiega la telemetria operativa raccolta per affidabilità e pianificazione della capacità.</p>
          <ul class="doc-fact-list">
            <li>Le chiavi di telemetria e gli esempi sono pubblicati affinché le affermazioni siano verificabili.</li>
            <li>L'ambito di raccolta è esclusivamente aggregato, con limiti severi e senza identificatori personali nelle chiavi.</li>
            <li>La conservazione segue un ciclo di vita definito: dati grezzi, aggregati e cancellazione automatica.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Accessibilità e conformità WCAG</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Utilizziamo WCAG 2.1 livello AA come standard di accessibilità di lavoro e pubblichiamo i lavori di accessibilità recenti in linguaggio semplice.</p>
          <ul class="doc-fact-list">
            <li>La navigazione principale supporta l'uso della tastiera, i link di salto e le scorciatoie a tasto singolo documentate per le destinazioni principali.</li>
            <li>La gestione delle scorciatoie è protetta e non si attiva durante la digitazione in campi modificabili o quando sono aperti i dialoghi.</li>
            <li>La copertura di regressione recente verifica intestazioni, reflow/spaziatura del testo, percorsi di navigazione e la consegna del feedback di accessibilità.</li>
            <li>I blocchi di contrasto rigorosi a livello di route nelle pagine pubbliche principali sono stati corretti; il lavoro sul contrasto a livello di tema continua.</li>
            <li>Gli utenti possono avviare un rapporto di accessibilità dalla pagina di accessibilità e continuarlo tramite il flusso di contatto sicuro.</li>
            <li>La pagina di trasparenza sull'accessibilità ora pubblica la data dell'ultima verifica, l'ambito di verifica, le limitazioni note e la prossima data di revisione.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Verifica e governance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Questa pagina documenta come PayCal applica le politiche attraverso test, hook, limiti runtime e controlli di sicurezza.</p>
          <ul class="doc-fact-list">
            <li>I hook pre-commit e pre-push applicano PHPStan livello 9 e rifiutano i bypass della baseline.</li>
            <li>La CI esegue la validazione a stadi su job unitari, di integrazione, di contratto, a ordine casuale e di copertura.</li>
            <li>I controlli runtime applicano limiti di frequenza, finestre TTL e blocchi di risposta agli abusi per i flussi sensibili.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Capacità di rete</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Questo articolo pubblica i protocolli di trasporto e i controlli degli header di risposta utilizzati per proteggere il comportamento del browser e della rete.</p>
          <ul class="doc-fact-list">
            <li>Documenta l'applicazione HTTPS, il precaricamento HSTS e l'annuncio HTTP/3 (QUIC).</li>
            <li>Elenca la baseline attuale degli header di sicurezza, inclusi CSP, COOP, COEP, CORP e gli header di rafforzamento del browser.</li>
            <li>Spiega la negoziazione del protocollo e il comportamento di fallback sui client moderni.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Governance dei test</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Questo articolo documenta come eseguiamo la validazione backend, frontend e di accessibilità e quali gate sono trattati come blocchi al rilascio.</p>
          <ul class="doc-fact-list">
            <li>Mostra l'inventario attivo delle suite PHPUnit e la suddivisione per categoria.</li>
            <li>Documenta i comandi di validazione bloccanti per il rilascio usati nelle sweep <code>/mis</code>.</li>
            <li>Spiega come le prove dei test vengono sincronizzate nei changelog e nelle note source-of-truth.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Governance delle dipendenze e CI/CD</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>Questo articolo pubblica come le dipendenze npm vengono controllate e come le gate CI vengono applicate prima del rilascio.</p>
          <ul class="doc-fact-list">
            <li>Documenta la politica npm lockfile-first e i requisiti di automazione <code>npm ci</code>.</li>
            <li>Mappa le gate di qualità JavaScript e le fasi della pipeline backend ai controlli del workflow.</li>
            <li>Elenca le limitazioni di documentazione note e i miglioramenti di governance pianificati.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Registro modifiche framework e backend</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Questa pagina traccia l'architettura backend e le modifiche a livello di framework con spiegazioni pubbliche di cosa è cambiato e perché.</p>
          <ul class="doc-fact-list">
            <li>Riepiloga le modifiche al servizio/controller che influenzano materialmente il comportamento.</li>
            <li>Mappa le modifiche del rilascio ai controlli di sicurezza e governance.</li>
            <li>Include riferimenti al changelog dettagliato e agli artefatti di audit.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Esperienza prodotto e modifiche alla fatturazione</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>I principali aggiornamenti di account, fatturazione e flussi del profilo sono spiegati insieme alla governance backend e di test affinché gli utenti possano controllare sia le modifiche UX che comportamentali.</p>
          <ul class="doc-fact-list">
            <li>Traccia la gestione dello stato di fatturazione e le modifiche al contratto di stato dell'abbonamento.</li>
            <li>Cattura le protezioni per le azioni distruttive come le frasi di conferma esplicite per l'eliminazione dell'account.</li>
            <li>Collega gli aggiornamenti rivolti al prodotto con le prove di verifica e governance del rilascio.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Metodologia fiscale</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>La pagina delle tasse documenta le nostre formule, soglie ed esempi allineati all'ARC utilizzati per le stime.</p>
          <ul class="doc-fact-list">
            <li>Le formule CPP, OAS, EI, imposta federale/provinciale e retribuzione netta sono documentate con esempi pratici.</li>
            <li>Le soglie e le aliquote dell'anno fiscale corrente sono pubblicate e collegate ai riferimenti dell'ARC.</li>
            <li>La qualità del calcolo è validata con una suite di test automatizzata e aggiornamenti annuali delle aliquote.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Architettura email</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>La pagina email spiega quali email transazionali invia PayCal, come vengono renderizzati i template e come viene verificata l'affidabilità della consegna.</p>
          <ul class="doc-fact-list">
            <li>Le famiglie di template specifiche per flusso sono documentate per i percorsi di verifica, recupero, cambio email e supporto contatti.</li>
            <li>Le responsabilità di consegna sono separate tra l'orchestrazione di EmailGarum e la gestione del protocollo SMTP di EmailTransport.</li>
            <li>I test live opt-in per le sweep dei template e la verifica della salute DKIM/DMARC sono documentati.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Test di carico earnings</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Questo articolo pubblica risultati di benchmark A/B riproducibili per il rendering anticipato rispetto al caricamento differito delle sezioni su <code>/earnings/</code>.</p>
          <ul class="doc-fact-list">
            <li>Include una matrice di 10 esecuzioni per dataset reali e sintetici 2025/2026.</li>
            <li>Riporta DOMContentLoaded, i tempi di sezione-pronta e i compromessi sulle chiamate API.</li>
            <li>Documenta il metodo di test e l'interpretazione per la revisione pubblica.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Mappa dei supereroi</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>La pagina dei Supereroi documenta i componenti trasversali tematici di PayCal e il problema operativo specifico che ciascuno risolve.</p>
          <ul class="doc-fact-list">
            <li>Include ShadowTalon, Guardian, Phantom Wing, Lens ed EmailGarum.</li>
            <li>Spiega dove viene utilizzato ciascun componente e quale confine di rischio protegge.</li>
            <li>Fornisce ancore di verifica in modo che le affermazioni di implementazione possano essere ispezionate direttamente nel codice e nei test.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
