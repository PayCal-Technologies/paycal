<?php
/**
 * Public Transparency: Business Membership and Role Philosophy
 *
 * PURPOSE:
 * Explain why PayCal uses a Business <-> Member relationship model,
 * how role changes are governed, and what architectural philosophy guides
 * capability, scope, and security decisions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Organisationsmitgliedschaft und Rollenphilosophie</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Diese Seite erklärt den Übergang von locker gekoppelter Gruppen-Semantik zu einem expliziten
      Organisations- <strong>&lt;-&gt;</strong>-Mitglieds-Beziehungsmodell, die aktuelle Rollenrichtlinie
      und die Prinzipien, die wir anwenden, um Berechtigungen überprüfbar und sicher zu halten.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Warum dieses Modell existiert</h2>
      <p>
        Die Gehaltsabrechnungszusammenarbeit hat echte Sicherheitsauswirkungen. Ein Rollenmodell,
        das leicht zu lesen, zu testen und zu prüfen ist, ist sicherer als ein Modell aus
        verstreuten Einzelfallprüfungen.
      </p>
      <p>
        Die Organisations- <strong>&lt;-&gt;</strong>-Mitglieds-Struktur gibt jedem Akteur eine
        explizite Beziehung zu einer Organisation mit richtlinienbewusstem Status-, Rollen-
        und Bereichsverhalten.
      </p>
    </section>

    <section class="doc-section">
      <h2>Änderungen der Organisations- <strong>&lt;-&gt;</strong>-Mitglieds-Beziehung</h2>
      <ul class="doc-list">
        <li>Mitgliedschaft wird als explizite Beziehung statt als impliziter UI-Status dargestellt.</li>
        <li>Zugriffsanfrage-, Einladungs-, Genehmigungs-, Aktivierungs- und Widerrufungslebenszyklus-Zustände werden durch Backend-Richtlinien durchgesetzt.</li>
        <li>Organisationspanels und Benachrichtigungen spiegeln jetzt Beziehungsübergänge und Rollenergebnisse konsistenter wider.</li>
        <li>Gemeinsames Organisationsverhalten wird durch den Mitgliedschaftsstatus geregelt, bevor privilegierte Aktionen verarbeitet werden.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Rollenänderungen und aktuelle Rollenphilosophie</h2>
      <p>
        Rollen sind kapazitätsgesteuert, mit pro Operation angewendeten Bereichsbeschränkungen. Die aktuelle Basis:
      </p>
      <ul class="doc-list">
        <li><strong>Eigentümer:</strong> souveräne Kontrolle einschließlich Eigentumsübertragung und hochvertrauenswürdiger Verwaltungsaktionen.</li>
        <li><strong>Manager:</strong> tägliche Betriebskontrolle ohne Eigentumsübertragungsbefugnis.</li>
        <li><strong>Mitwirkender:</strong> vertrauenswürdiger Operator mit Schreibberechtigung, begrenzt durch den zugewiesenen Bereich.</li>
        <li><strong>Mitglied:</strong> begrenzte Self-Service-Teilnahme mit eingeschränkten Änderungsrechten.</li>
        <li><strong>Betrachter:</strong> schreibgeschützte Sichtbarkeit ohne Schreibberechtigungen.</li>
      </ul>
      <p>
        Wir bevorzugen explizite Kapazitäts- und Bereichszusammensetzung gegenüber überladenen Rollenkennzeichnungen. Dies macht Rollenergebnisse einfacher zu testen und nachzuvollziehen.
      </p>
    </section>

    <section class="doc-section">
      <h2>Sicherheits- und Verschlüsselungsphilosophie</h2>
      <p>
        Die Organisationszusammenarbeit überschneidet sich mit Verschlüsselungs- und Zustimmungskontrollen.
        Mitgliedschafts- und Rollenprüfungen steuern das gemeinsame Organisations-Envelope-Verhalten,
        sodass sensible Operationen richtliniengebunden bleiben.
      </p>
      <ul class="doc-list">
        <li>Mitgliedschafts- und Zustimmungsstatus werden vor dem Fortschreiten geteilter sicherer Operationen validiert.</li>
        <li>Rollenänderungen und Mitgliedschaftsübergänge werden als sicherheitsrelevante Ereignisse behandelt, nicht nur als UX-Ereignisse.</li>
        <li>Zugriffsverweigerungspfade sind erwartetes Verhalten bei Richtlinienabweichungen und werden zur Überprüfbarkeit sichtbar gemacht.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Operative Philosophie für die Zukunft</h2>
      <ul class="doc-list">
        <li><strong>Einzelne Richtlinienquelle:</strong> Rollen- und Bereichsentscheidungen sollten aus gemeinsamen Backend-Richtlinien-Maps stammen.</li>
        <li><strong>UI als Projektion:</strong> Schnittstellen sollten Richtlinienergebnisse anzeigen, anstatt Autorisierungslogik zu duplizieren.</li>
        <li><strong>Nachverfolgbare Übergänge:</strong> Genehmigungen, Rollenänderungen und Widerrufe sollten beobachtbar und überprüfbar bleiben.</li>
        <li><strong>Versions-Transparenz:</strong> Verhaltensänderungen bei Mitgliedschaft und Rollen werden in Changelogs und Transparenzseiten dokumentiert.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
