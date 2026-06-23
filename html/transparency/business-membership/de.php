<?php
/**
 * Public Transparency: Business Connections and Role Philosophy
 *
 * PURPOSE:
 * Explain how PayCal separates business connections, active membership,
 * role changes, consent, and explicit access grants.
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
    <span class="current">Geschäftsverbindungen und Rollenphilosophie</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Diese Seite erklärt den Wechsel von locker gekoppelter Gruppen-Semantik zu expliziten
      Verbindungen. Eine Verbindung sagt, wer mit wem verknüpft ist. Mitgliedschaft, Rolle,
      Zustimmung und geschützter Datenzugriff bleiben getrennte Richtlinienentscheidungen.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/business-membership-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
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
        Die Business- <strong>&lt;-&gt;</strong>-Mitglied-Verbindung gibt jedem Akteur einen expliziten
        Identitätslink zu einem Business. Aktive Mitgliedschaft, Rollenautorität, Zustimmung
        zu geschützten Daten und künftige Person-zu-Person-Freigaben bleiben davon getrennt.
      </p>
    </section>

    <section class="doc-section">
      <h2>Änderungen der Business- <strong>&lt;-&gt;</strong>-Mitglied-Verbindung</h2>
      <ul class="doc-list">
        <li>Verbindungen werden explizit dargestellt, statt aus dem UI-Zustand abgeleitet zu werden.</li>
        <li>Zugriffsanfrage-, Einladungs-, Genehmigungs-, Aktivierungs- und Widerrufungslebenszyklus-Zustände werden durch Backend-Richtlinien durchgesetzt.</li>
        <li>Business-Panels und Benachrichtigungen spiegeln Verbindungsübergänge und Rollenergebnisse konsistenter wider.</li>
        <li>Gemeinsames Business-Verhalten wird durch aktive Mitgliedschaft und Rollenrichtlinien geregelt, bevor privilegierte Aktionen verarbeitet werden.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Verbindung, Mitgliedschaft, Zustimmung und Freigaben</h2>
      <p>
        PayCal behandelt diese Konzepte jetzt getrennt:
      </p>
      <ul class="doc-list">
        <li><strong>Verbindung:</strong> ein Identitätslink zwischen einer Person und einem Business oder zwischen zwei Personen.</li>
        <li><strong>Mitgliedschaft:</strong> der aktive Business-Teilnahmestatus für Workspace-Zusammenarbeit.</li>
        <li><strong>Zustimmung:</strong> die Freigabe eines Mitglieds zum Teilen geschützter Arbeitsdaten.</li>
        <li><strong>Grant:</strong> eine ausdrückliche Berechtigung, etwa delegierte Kalendersicht oder eine künftige vertrauenswürdige Wiederherstellungsfunktion.</li>
      </ul>
      <p>
        Eine Verbindung allein gewährt keine geschützten Berichte, Exporte, Gehaltsabrechnungssicht,
        Wiederherstellungsautorität oder die Fähigkeit, für eine andere Person zu handeln.
      </p>
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
        Business-Zusammenarbeit überschneidet sich mit Verschlüsselungs- und Zustimmungskontrollen.
        Aktive Mitgliedschaft, Rollenprüfungen und Zustimmungsstatus steuern das gemeinsame Business-Envelope-Verhalten,
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
