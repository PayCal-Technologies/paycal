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
$pageTitle = 'Erweiterungsparadigma - [PayCal]';
$pageLabel = 'Erweiterungsparadigma';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Erweiterungsparadigma</span>
  </nav>

  <header class="doc-article-header">
    <h1>Erweiterungsparadigma</h1>
    <p class="deck">
      PayCal ist so konzipiert, dass die zentrale Geschäftslogik stabil bleibt, während
      Erweiterungsschichten Funktionen für verschiedene Bereitstellungen und
      Produktstrategien anpassen können.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Core-First-Architektur</h2>
      <p>
        <strong>PayCal Core</strong> enthält die kanonische Domänen- und Controller-Logik:
        Berechnungen, Validierung, Berechtigungen, Lebenszyklusrichtlinien und gemeinsame API-Verträge.
      </p>
      <p>
        Core bleibt designbedingt erweiterungsunabhängig. Integrationspunkte sind durch
        Bridge-Verträge isoliert, sodass Core-Dienste unabhängig von laufzeitspezifischen
        Paketen getestet werden können.
      </p>
    </section>

    <section class="doc-section">
      <h2>In diesem Repository enthaltene Basiserweiterungen</h2>
      <p>
        Dieses Repository enthält <strong>grundlegende Erweiterungsimplementierungen</strong>,
        die ein Standardverhalten für Erweiterungspunkte bereitstellen. Sie dienen als
        öffentliche Referenzpakete und sichere Standardwerte für selbst gehostete Bereitstellungen.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> grundlegende Abrechnungsfähigkeits-Hooks und Modusauswahl</li>
        <li><strong>earnings-ytd:</strong> grundlegendes YTD-Rendering und Einnahmen-Hook-Punkte</li>
        <li><strong>organization-signals:</strong> grundlegende Organisations-Signal-Hooks</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Drittanbieter-Erweiterungsmodell</h2>
      <p>
        Dritte, die dieses Repository verwenden, können eigene Erweiterungspakete erstellen
        und pflegen. Das empfohlene Modell lautet:
      </p>
      <ol class="doc-list">
        <li>Core-Logik nach Möglichkeit unverändert lassen</li>
        <li>Benutzerdefiniertes Verhalten in Erweiterungspaketen implementieren</li>
        <li>Benutzerdefinierte Pakete über dokumentierte Erweiterungs-Bootstrap- und Hook-Punkte einbinden</li>
        <li>Core-Verträge beibehalten, damit Upstream-Upgrades handhabbar bleiben</li>
      </ol>
      <p>
        Dies ermöglicht wettbewerbsfähige und vertikalspezifische Bereitstellungen ohne
        erzwungene langfristige Forks des zentralen Domänencodes.
      </p>
    </section>

    <section class="doc-section">
      <h2>Differenzierung der kanonischen paycal.app-Plattform</h2>
      <p>
        Die kanonische Plattform <code>https://paycal.app</code> betreibt
        <strong>private Erweiterungsvarianten</strong> auf demselben Core und grundlegenden
        Erweiterungsparadigma.
      </p>
      <p>
        Diese privaten Varianten sind eine bewusste Produktdifferenzierungsschicht für von
        PayCal betriebene Umgebungen. Sie können Workflows, Kapazitätsverhalten und
        UI-spezifische Integrationen anpassen und dabei die Kompatibilität mit derselben
        Kernarchitektur bewahren.
      </p>
      <ul class="doc-list">
        <li>Core-Logik bleibt geteilt und prüfbar</li>
        <li>Öffentliche/grundlegende Erweiterungen bleiben im Repository verfügbar</li>
        <li>Private Erweiterungen liefern die Differenzierung der kanonischen Plattform</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Transparenzverpflichtungen</h2>
      <ul class="doc-list">
        <li>Core-Verträge sind an Erweiterungspunkten dokumentiert und getestet</li>
        <li>Bridge-Grenzen sind explizit, um Kopplung auffindbar zu machen</li>
        <li>Erweiterungsverhalten kann sich weiterentwickeln, ohne Core-Dienste zu destabilisieren</li>
        <li>Selbst hostende Anwender können alternative Erweiterungsstrategien entwickeln</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
