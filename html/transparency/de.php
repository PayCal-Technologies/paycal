<?php
/**
 * Public Transparency Hub — Deutsch
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

$readMoreLabel = 'Mehr erfahren';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Transparenzzentrum - [PayCal]';
$pageLabel = 'Transparenzzentrum';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">Transparenzzentrum</span>
    </nav>

    <header class="doc-article-header">
      <h1>Transparenzzentrum</h1>
      <p class="deck">Wir veröffentlichen, wie PayCal funktioniert, damit Nutzer Entscheidungen überprüfen können – nicht nur Aussagen vertrauen müssen.</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>Philosophie der Plattform</h2>
        <p>PayCal basiert auf inspektierbaren Abläufen: Formeln sind dokumentiert, Telemetrielimits sind explizit und Aufbewahrungsfristen standardmäßig begrenzt.</p>
        <p>Unser Grundsatz: Wenn ein System die Gehaltsabrechnung oder den Datenschutz betrifft, sollen Nutzer nachvollziehen können, wie es funktioniert und wie es geregelt ist.</p>
        <p>Die Abonnementabrechnung erfolgt über Stripe. Stripe-Support ist verfügbar unter <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a>.</p>
        <p>Neueste Änderungen, die das Produkt geprägt haben – einschließlich Abrechnungs- und Profilgovernance-Abläufe – werden auf den Seiten Framework/Backend und Testgovernance verfolgt.</p>
      </section>

      <div class="doc-panel-grid" aria-label="Transparenz-Detailpanels">
        <section class="doc-section">
          <h2>Sicherheitsaudit-Status</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>Diese Seite veröffentlicht den aktuellen Auditstatus, den abgeschlossenen Umfang, Belegnachweise und Release-Blockierungsverpflichtungen zum Schutz der Sicherheitslage.</p>
          <ul class="doc-fact-list">
            <li>Der Status des aktuellen Zyklus wird mit Verifikationsdatum und Überprüfungsrhythmus veröffentlicht.</li>
            <li>Die Abdeckung umfasst Runtime-Lebenszyklus-Kontrollen, Telemetrie-Isolation, Korrelationsgovernance und Härtung privilegierter Rollen.</li>
            <li>Das Validierungs-Snapshot enthält Playwright, JS, PHPStan Level 9 und Backend-Testergebnisse.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Plattformmetriken und Datenschutz</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>Die Metrik-Seite erklärt die operationelle Telemetrie, die für Zuverlässigkeit und Kapazitätsplanung erfasst wird.</p>
          <ul class="doc-fact-list">
            <li>Telemetrie-Schlüssel und Beispiele werden veröffentlicht, damit Aussagen überprüfbar sind.</li>
            <li>Der Erfassungsumfang ist ausschließlich aggregiert, mit strengen Limits und ohne persönliche Identifikatoren in Schlüsseln.</li>
            <li>Die Aufbewahrung folgt einem definierten Lebenszyklus: Rohdaten, Aggregate und automatische Löschung.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Barrierefreiheit und WCAG-Konformität</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Wir verwenden WCAG 2.1 Level AA als Arbeitsstandard und veröffentlichen aktuelle Barrierefreiheitsarbeiten in klarer Sprache.</p>
          <ul class="doc-fact-list">
            <li>Die Hauptnavigation unterstützt Tastaturnutzung, Skip-Links und dokumentierte Einzeltasten-Shortcuts für Hauptziele.</li>
            <li>Shortcut-Behandlung ist gesichert und wird während der Eingabe in Felder oder bei offenen Dialogen nicht ausgelöst.</li>
            <li>Aktuelle Regressionstests prüfen Überschriften, Reflow/Textabstände, Navigationspfade und Barrierefreiheits-Feedback.</li>
            <li>Strikte routenbezogene Kontrastblockierungen auf öffentlichen Kernseiten wurden behoben; themenweite Kontrastarbeiten laufen weiter.</li>
            <li>Nutzer können einen Barrierefreiheitsbericht über die Barrierefreiheitsseite starten und über den sicheren Kontaktfluss fortsetzen.</li>
            <li>Die Barrierefreiheits-Transparenzseite veröffentlicht jetzt das zuletzt geprüfte Datum, den Prüfungsumfang, bekannte Einschränkungen und das nächste Prüfdatum.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Verifikation und Governance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Diese Seite dokumentiert, wie PayCal Richtlinien durch Tests, Hooks, Runtime-Limits und Sicherheitskontrollen durchsetzt.</p>
          <ul class="doc-fact-list">
            <li>Pre-commit- und Pre-push-Hooks erzwingen PHPStan Level 9 und lehnen Baseline-Umgehungen ab.</li>
            <li>CI führt gestaffelte Validierung über Unit-, Integrations-, Vertrags-, Zufallsreihenfolge- und Coverage-Jobs durch.</li>
            <li>Runtime-Kontrollen wenden Ratenlimits, TTL-Fenster und Missbrauchs-Antwortblockierungen für sensible Abläufe an.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Netzwerkfähigkeiten</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Dieser Artikel veröffentlicht die Transportprotokolle und Antwortheader-Kontrollen zur Sicherung des Browser- und Netzwerkverhaltens.</p>
          <ul class="doc-fact-list">
            <li>Dokumentiert HTTPS-Durchsetzung, HSTS-Preload und HTTP/3 (QUIC)-Ankündigung.</li>
            <li>Listet die aktuelle Sicherheitsheader-Baseline einschließlich CSP, COOP, COEP, CORP und Browser-Härtungsheadern auf.</li>
            <li>Erklärt Protokollaushandlung und Fallback-Verhalten bei modernen Clients.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Test-Governance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Dieser Artikel dokumentiert Backend-, Frontend- und Barrierefreiheitsvalidierung und welche Gates als Release-Blockierungen behandelt werden.</p>
          <ul class="doc-fact-list">
            <li>Zeigt das aktive PHPUnit-Suite-Inventar und die Kategorieaufteilung.</li>
            <li>Dokumentiert Release-blockierende Validierungsbefehle in <code>/mis</code> Sweeps.</li>
            <li>Erklärt, wie Testbelege in Changelogs und Source-of-Truth-Notizen synchronisiert werden.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Abhängigkeits- und CI/CD-Governance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>Dieser Artikel veröffentlicht, wie npm-Abhängigkeiten kontrolliert und CI-Gates vor Release durchgesetzt werden.</p>
          <ul class="doc-fact-list">
            <li>Dokumentiert Lockfile-First npm-Richtlinie und <code>npm ci</code> Automatisierungsanforderungen.</li>
            <li>Ordnet JavaScript-Qualitätsgates und Backend-Pipeline-Stufen Workflow-Kontrollen zu.</li>
            <li>Listet bekannte Dokumentationslimits und geplante Governance-Verbesserungen auf.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Framework- und Backend-Änderungsprotokoll</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Diese Seite verfolgt Backend-Architektur und Framework-Änderungen mit öffentlichen Erklärungen, was sich geändert hat und warum.</p>
          <ul class="doc-fact-list">
            <li>Fasst Service-/Controller-Änderungen zusammen, die das Verhalten wesentlich beeinflussen.</li>
            <li>Ordnet Release-Änderungen Sicherheits- und Governance-Kontrollen zu.</li>
            <li>Enthält Verweise auf detaillierten Changelog und Audit-Artefakte.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Produkt- und Abrechnungsänderungen</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Wesentliche Konto-, Abrechnungs- und Profilablauf-Updates werden neben Backend- und Test-Governance erklärt, damit Nutzer UX- und Verhaltensänderungen prüfen können.</p>
          <ul class="doc-fact-list">
            <li>Verfolgt Abrechnungsstatus-Handling und Abonnementstatus-Vertragsänderungen.</li>
            <li>Erfasst Schutzmaßnahmen für destruktive Aktionen wie explizite Kontoauflösungs-Bestätigungsphrasen.</li>
            <li>Verknüpft produktseitige Updates mit Verifikations- und Release-Governance-Belegen.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Steuermethodik</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>Die Steuerseite dokumentiert unsere CRA-orientierten Formeln, Schwellenwerte und Beispiele für Schätzungen.</p>
          <ul class="doc-fact-list">
            <li>CPP-, OAS-, EI-, Bundes-/Provinzsteuer- und Nettolohnformeln sind mit Rechenbeispielen dokumentiert.</li>
            <li>Aktuelle Steuerjahr-Schwellenwerte und -Sätze sind veröffentlicht und mit CRA-Referenzen verknüpft.</li>
            <li>Die Berechnungsqualität wird durch eine automatisierte Testsuite und jährliche Satz-Updates validiert.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>E-Mail-Architektur</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>Die E-Mail-Seite erklärt, welche Transaktions-E-Mails PayCal sendet, wie Templates gerendert werden und wie Lieferzuverlässigkeit verifiziert wird.</p>
          <ul class="doc-fact-list">
            <li>Ablauf-spezifische Template-Familien sind für Verifikations-, Wiederherstellungs-, E-Mail-Änderungs- und Kontakt-Support-Pfade dokumentiert.</li>
            <li>Lieferverantwortlichkeiten sind zwischen EmailGarum-Orchestrierung und EmailTransport-SMTP-Protokollhandling getrennt.</li>
            <li>Opt-in-Live-Tests für Template-Sweeps und DKIM/DMARC-Gesundheitsverifizierung sind dokumentiert.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Earnings-Lasttests</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Dieser Artikel veröffentlicht reproduzierbare A/B-Benchmark-Ergebnisse für Eager-Rendering versus Lazy Section Loading auf <code>/earnings/</code>.</p>
          <ul class="doc-fact-list">
            <li>Enthält eine 10-Run-Matrix für echte und synthetische 2025/2026-Datensätze.</li>
            <li>Berichtet DOMContentLoaded, Section-Ready-Timing und API-Aufruf-Kompromisse.</li>
            <li>Dokumentiert Testmethode und Interpretation zur öffentlichen Überprüfung.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Superhelden-Karte</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>Die Superhelden-Seite dokumentiert PayCals bereichsübergreifende Komponenten und das spezifische operationelle Problem, das jede löst.</p>
          <ul class="doc-fact-list">
            <li>Enthält ShadowTalon, Guardian, Phantom Wing, Lens und EmailGarum.</li>
            <li>Erklärt, wo jede Komponente eingesetzt wird und welche Risikogrenze sie schützt.</li>
            <li>Bietet Verifikationsanker, damit Implementierungsaussagen direkt in Code und Tests überprüft werden können.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
