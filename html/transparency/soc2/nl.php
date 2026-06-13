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
  <nav class="doc-breadcrumb" aria-label="Broodkruimelpad">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Transparantiehub</a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">Een technisch overzicht van hoe PayCal SOC 2-controles koppelt aan afgedwongen systeemgedrag en continu gegenereerde bewijzen.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Overzicht</h2>
      <p>PayCal beheert een SOC 2-conform beveiligingsprogramma dat gericht is op verifieerbare handhaving en traceerbare bewijzen, niet op beleidsverklaringen alleen.</p>
      <ul class="doc-fact-list">
        <li><strong>Controles binnen scope:</strong> CC1-CC9</li>
        <li><strong>Artefacten in het huidige bundle:</strong> 37</li>
        <li><strong>Controle-naar-artefact-koppelingen:</strong> 26</li>
        <li><strong>Versheidsvenster voor bewijzen:</strong> 35 dagen</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Controle-dekking (CC1-CC9)</h2>
      <p>Alle SOC 2 Common Criteria-controles binnen scope (CC1 tot en met CC9) zijn gekoppeld aan bewijzen die zijn opgeslagen in het maandelijkse bundle.</p>
      <p>Deze koppeling ondersteunt directe traceerbaarheid van controledoelstelling tot concrete artefacten die voor beoordeling worden gebruikt.</p>
    </section>

    <section class="doc-section">
      <h2>3. Hoe controles worden afgedwongen</h2>
      <p>PayCal behandelt handhaving als een systeemeigenschap. Controles worden programmatisch afgedwongen, niet alleen gedocumenteerd.</p>
      <ul class="doc-fact-list">
        <li><strong>Authenticatie:</strong> Passkey-geschikt authenticatieproces voor versterkte phishing-weerstand.</li>
        <li><strong>Runtime-integriteit:</strong> Runtime-integriteitsmonitoring met operationele statusverwerking.</li>
        <li><strong>Outputbeveiliging:</strong> Guardian-saniteringscontroles voor gevoelige DOM/outputpaden.</li>
        <li><strong>Kwaliteitspoort:</strong> Geautomatiseerde volledige PHPUnit-poort voordat bundle-bewijzen worden geaccepteerd.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Wijzigingsbeheer &amp; Tests</h2>
      <p>Het wijzigingsbeheer is afgestemd op CC8 met gevolgde wijzigingen, goedkeuringen en testbewijzen.</p>
      <ul class="doc-fact-list">
        <li><strong>Wijzigingsrecords:</strong> 12</li>
        <li><strong>Goedkeuringsrecords:</strong> 10</li>
        <li><strong>Testresultaten:</strong> 1528 tests, 8351 assertions (geslaagd)</li>
        <li><strong>Test-controle-tracing:</strong> 5 suites, 5 geslaagd, 8 gekoppelde testbestanden</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Audittrail &amp; Bewijsintegriteit</h2>
      <p>Administratieve en beveiligingsrelevante runtime-gebeurtenissen worden geëxporteerd met onveranderlijke ledger-validatie voor integriteitscontroles.</p>
      <p><strong>Huidige ledger-integriteitsstatus:</strong> GESLAAGD.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Continue monitoring &amp; Versheid</h2>
      <p>Bewijsexports worden continu uitgevoerd en gevalideerd aan de hand van een deterministisch versheidsbeleid.</p>
      <p><strong>Huidig versheidsresultaat:</strong> alle gekoppelde artefacten vallen binnen het 35-daagse auditvenster.</p>
    </section>

    <section class="doc-section">
      <h2>7. Huidige status</h2>
      <p><strong>Status:</strong> SOC 2 gereedheid in uitvoering, met continue controleversterking en deterministische bewijs-updates.</p>
      <p>PayCal claimt geen SOC 2-certificering of auditeursoordeel op deze pagina. Toegang tot het formele rapport blijft NDA-beveiligd.</p>
    </section>

    <section class="doc-section">
      <h2>Herbruikbare nalevingsfragmenten</h2>
      <p><strong>Footerbadge:</strong> Gereedheid in uitvoering • Controles Gekoppeld • Continue bewijs-monitoring</p>
      <p><strong>Samenvattingsblok:</strong> CC1-CC9 gekoppeld, 37 artefacten, 26 controlekoppelingen, ledgerintegriteit geslaagd en geautomatiseerd volledig testbewijs.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Referenties</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Opgeschoonde openbare controlesamenvatting, deterministische narratieven en beveiligingscontactpad.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">PayCal SOC 2 Samenvatting</a>
          <span class="doc-ref-desc">Status, statistieken en NDA-toegang voor dit rapport.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">SOC 2-rapport aanvragen (NDA)</a>
          <span class="doc-ref-desc">Beveiligde toegang voor leveranciers- en beveiligings-due diligence-beoordelingen.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Officiële standaard</a>
          <span class="doc-ref-desc">Het gezaghebbende raamwerk dat de SOC 2-criteria definieert.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Overzicht van de geschiedenis en reikwijdte van systeem- en organisatiecontroles.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Reddit-community</a>
          <span class="doc-ref-desc">Discussie van beoefenaars over SOC 2-audits en voorbereiding.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
