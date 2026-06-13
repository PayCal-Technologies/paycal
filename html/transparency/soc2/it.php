<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_SOC2_PAGE_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'];


require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars($i18n['BREADCRUMB'], ENT_QUOTES, 'UTF-8'); ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Hub di trasparenza</a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">Una visione tecnica di come PayCal mappa i controlli SOC 2 sui comportamenti di sistema applicati e sulle prove generate continuamente.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Panoramica</h2>
      <p>PayCal gestisce un programma di sicurezza orientato a SOC 2, incentrato sull'applicazione verificabile e sulle prove tracciabili, soggetto a validazione indipendente da parte di un revisore.</p>
      <ul class="doc-fact-list">
        <li><strong>Controlli nel perimetro:</strong> CC1-CC9</li>
        <li><strong>Artefatti nel bundle corrente:</strong> 37</li>
        <li><strong>Mappature controllo-artefatto:</strong> 26</li>
        <li><strong>Finestra di validità delle prove:</strong> 35 giorni</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Copertura dei controlli (CC1-CC9)</h2>
      <p>Tutti i controlli SOC 2 Common Criteria nel perimetro (da CC1 a CC9) sono mappati sulle prove conservate nel bundle mensile.</p>
      <p>Questa mappatura supporta la tracciabilità diretta dall'obiettivo di controllo agli artefatti concreti utilizzati per la revisione.</p>
    </section>

    <section class="doc-section">
      <h2>3. Come vengono applicati i controlli</h2>
      <p>PayCal tratta l'applicazione come una proprietà del sistema. I controlli vengono applicati programmaticamente, non solo documentati.</p>
      <ul class="doc-fact-list">
        <li><strong>Autenticazione:</strong> Flusso di autenticazione con supporto passkey per rafforzare la resistenza al phishing.</li>
        <li><strong>Integrità a runtime:</strong> Monitoraggio dell'integrità a runtime con gestione dello stato operativo.</li>
        <li><strong>Hardening degli output:</strong> Controlli di sanificazione Guardian per i percorsi DOM/output sensibili.</li>
        <li><strong>Barriera di qualità:</strong> Barriera PHPUnit a suite completa automatizzata prima che le prove del bundle vengano accettate.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Gestione delle modifiche &amp; Test</h2>
      <p>La governance delle modifiche è allineata a CC8 con modifiche tracciate, approvazioni e prove di test.</p>
      <ul class="doc-fact-list">
        <li><strong>Record di modifiche:</strong> 12</li>
        <li><strong>Record di approvazione:</strong> 10</li>
        <li><strong>Risultati dei test:</strong> 1528 test, 8351 assertion (superati)</li>
        <li><strong>Traccia test-controllo:</strong> 5 suite, 5 superate, 8 file di test collegati</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Audit trail &amp; Integrità delle prove</h2>
      <p>Gli eventi di runtime amministrativi e rilevanti per la sicurezza vengono esportati con validazione tramite registro immutabile per le verifiche di integrità.</p>
      <p><strong>Stato attuale dell'integrità del registro:</strong> SUPERATO.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Monitoraggio continuo &amp; Validità</h2>
      <p>Le esportazioni di prove vengono eseguite continuamente e validate rispetto a una policy di validità deterministica.</p>
      <p><strong>Risultato di validità attuale:</strong> tutti gli artefatti mappati sono all'interno della finestra di audit di 35 giorni.</p>
    </section>

    <section class="doc-section">
      <h2>7. Stato attuale</h2>
      <p><strong>Stato:</strong> Preparazione SOC 2 in corso, con rafforzamento continuo dei controlli e aggiornamenti deterministici delle prove.</p>
      <p>PayCal non rivendica la certificazione SOC 2 né un'opinione del revisore su questa pagina. L'accesso al report formale rimane vincolato a NDA.</p>
    </section>

    <section class="doc-section">
      <h2>Frammenti di conformità riutilizzabili</h2>
      <p><strong>Badge footer:</strong> Preparazione in corso • Controlli mappati • Monitoraggio continuo delle prove</p>
      <p><strong>Blocco di riepilogo:</strong> CC1-CC9 mappati, 37 artefatti, 26 link di controllo, integrità del registro superata e prove di test a suite completa automatizzata.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Riferimenti</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Sintesi del controllo pubblico sanificato, narrativi deterministici e percorso di contatto per la sicurezza.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">Riepilogo SOC 2 di PayCal</a>
          <span class="doc-ref-desc">Stato, metriche e accesso NDA per questo report.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">Richiedi report SOC 2 (NDA)</a>
          <span class="doc-ref-desc">Accesso vincolato per revisioni di due diligence di fornitori e sicurezza.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Standard ufficiale</a>
          <span class="doc-ref-desc">Il framework autorevole che definisce i criteri SOC 2.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Panoramica della storia e dell'ambito dei controlli di sistema e organizzativi.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Community Reddit</a>
          <span class="doc-ref-desc">Discussione tra professionisti su audit SOC 2 e preparazione.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
