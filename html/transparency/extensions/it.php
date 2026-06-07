<?php
/**
 * Public Transparency: Extensions Paradigm
 *
 * PURPOSE:
 * Explain how PayCal separates core logic from extension layers, how third
 * parties can build custom extensions from this repository, and how
 * canonical paycal.app differentiates through private extension packages.
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
$pageTitle = 'Paradigma delle estensioni - [PayCal]';
$pageLabel = 'Paradigma delle estensioni';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Paradigma delle estensioni</span>
  </nav>

  <header class="doc-article-header">
    <h1>Paradigma delle estensioni</h1>
    <p class="deck">
      PayCal è progettato in modo che la logica aziendale centrale rimanga stabile mentre
      i livelli di estensione possono adattare le funzionalità per diverse distribuzioni
      e strategie di prodotto.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Architettura Core-first</h2>
      <p>
        <strong>PayCal Core</strong> contiene la logica canonica di dominio e dei controller:
        calcoli, validazione, permessi, policy del ciclo di vita e contratti API condivisi.
      </p>
      <p>
        Il Core rimane indipendente dalle estensioni per progettazione. I punti di integrazione
        sono isolati tramite contratti di bridge in modo che i servizi core possano essere
        testati indipendentemente dai pacchetti specifici del runtime.
      </p>
    </section>

    <section class="doc-section">
      <h2>Estensioni di base incluse in questo repository</h2>
      <p>
        Questo repository include <strong>implementazioni di estensioni di base</strong> che
        forniscono il comportamento predefinito per i punti di estensione. Fungono da pacchetti
        di riferimento pubblici e valori predefiniti sicuri per le distribuzioni self-hosted.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> hook di capacità di fatturazione di base e selezione della modalità</li>
        <li><strong>earnings-ytd:</strong> rendering YTD di base e punti hook dei guadagni</li>
        <li><strong>organization-signals:</strong> hook di segnale organizzativo di base</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Modello di estensione di terze parti</h2>
      <p>
        Le terze parti che utilizzano questo repository possono creare e gestire i propri
        pacchetti di estensione. Il modello raccomandato è:
      </p>
      <ol class="doc-list">
        <li>Mantenere la logica del Core non modificata quando possibile</li>
        <li>Implementare il comportamento personalizzato nei pacchetti di estensione</li>
        <li>Collegare i pacchetti personalizzati tramite il bootstrap di estensione documentato e i punti hook</li>
        <li>Preservare i contratti del Core in modo che gli aggiornamenti upstream rimangano gestibili</li>
      </ol>
      <p>
        Ciò consente distribuzioni competitive e specifiche per settore verticale senza forzare
        fork a lungo termine del codice di dominio centrale.
      </p>
    </section>

    <section class="doc-section">
      <h2>Differenziazione della piattaforma canonica paycal.app</h2>
      <p>
        La piattaforma canonica <code>https://paycal.app</code> esegue <strong>varianti di
        estensione private</strong> sopra lo stesso Core e paradigma di estensioni di base.
      </p>
      <p>
        Queste varianti private sono uno strato di differenziazione del prodotto deliberato per
        gli ambienti gestiti da PayCal. Possono ottimizzare i flussi di lavoro, il comportamento
        delle funzionalità e le integrazioni specifiche dell'interfaccia utente preservando la
        compatibilità con la stessa architettura centrale.
      </p>
      <ul class="doc-list">
        <li>La logica del Core rimane condivisa e verificabile</li>
        <li>Le estensioni pubbliche/di base rimangono disponibili nel repository</li>
        <li>Le estensioni private forniscono la differenziazione della piattaforma canonica</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Impegni di trasparenza</h2>
      <ul class="doc-list">
        <li>I contratti del Core sono documentati e testati nei punti di estensione</li>
        <li>I confini del bridge sono espliciti per rendere il coupling individuabile</li>
        <li>Il comportamento delle estensioni può evolversi senza destabilizzare i servizi core</li>
        <li>Gli adottanti self-hosted sono liberi di costruire strategie di estensione alternative</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
