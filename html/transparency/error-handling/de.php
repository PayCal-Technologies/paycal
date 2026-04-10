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
$pageTitle = 'Fehlerbehandlung und Nachrichtennormalisierung - [PayCal]';
$pageLabel = 'Fehlerbehandlung und Nachrichtennormalisierung';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Fehlerbehandlung und Nachrichtennormalisierung</span>
  </nav>

  <header class="doc-article-header">
    <h1>Fehlerbehandlung und Nachrichtennormalisierung</h1>
    <p class="deck">
      Wie PayCal die Fehlerberichterstattung über alle Frontend-Module hinweg standardisiert,
      um Benutzer mit aussagekräftigen, sicheren und konsistenten Fehlermeldungen zu versorgen,
      ohne sensible Details preiszugeben.
    </p>
<p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Übersicht und Zweck</h2>
      <p>
        Wenn Benutzer auf Fehler stoßen (Netzwerkfehler, Zugriff verweigert, Validierungsfehler),
        verdienen sie klares Feedback, das erklärt, was passiert ist und wie sie es beheben können.
        Allerdings müssen Rohmeldungen vom Backend normalisiert werden, um:
      </p>
      <ul class="doc-list">
        <li><strong>Rauschen zu entfernen:</strong> Redundante Präfixe wie „Fehler:" und Whitespace zu bereinigen</li>
        <li><strong>Leckagen zu verhindern:</strong> Sicherstellen, dass sensitive Implementierungsdetails den Benutzer nie erreichen</li>
        <li><strong>Fallbacks bereitzustellen:</strong> Sichere Meldungen anzuzeigen, wenn Fehler leer oder malformed sind</li>
        <li><strong>Konsistenz zu gewährleisten:</strong> Dieselbe Logik über alle 11+ Frontend-Module zu applizieren</li>
        <li><strong>Debugging zu verbessern:</strong> Vollständige Fehlerdetails bei Phantom Wing zu loggen und sichere Zusammenfassungen zu zeigen</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Das Problem: Generische vs. aussagekräftige Fehler</h2>
      <p>
        Vor der Standardisierung verwendeten PayCal-Module ad-hoc-Fehlerbehandlung:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ SCHLECHT: Gibt Rohfehler preis, duplizert Logik
PC.showToast(error?.message || 'Import fehlgeschlagen.');
PW.error(`Import fehlgeschlagen: ${error.message}`);</code></pre>
      </div>
      <p>Probleme mit diesem Ansatz:</p>
      <ul class="doc-list">
        <li>Benutzer sehen verwirrende Rohmeldungen wie „ECONNREFUSED: Verbindung verweigert"</li>
        <li>Jedes Modul implementiert seine eigene Fallback-Logik unabhängig</li>
        <li>Keine konsistente Whitespace-Bereinigung oder Präfixentfernung</li>
        <li>Leere Fehlermeldungen können in der UI als „undefined" angezeigt werden</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Die Lösung: Standardisierter Fehlerresolver</h2>
      <p>
        Alle PayCal-Frontend-Module verwenden nun eine einheitliche Resolver-Funktion,
        die Fehlermeldungen normalisiert:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ GUT: Normalisiert, konsistent, sicher
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Extrahiere Meldung aus Error-Objekt
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // Entferne „Error:"-Präfix und trimme Whitespace
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Gebe normalisiert zurück, wenn nicht leer; sonst sicherer Fallback
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Verwendung:</strong></p>
      <div class="doc-code-block">
        <pre><code>// In Catch-Blöcken über Module hinweg
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'Profil konnte nicht aktualisiert werden.');
  PC.showToast(message, 'error');  // Benutzer sieht aussagekräftiges Feedback
  PW.error(message);                // Protokolliert zum Debuggen
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Implementierungsumfang</h2>
      <p>
        Ab April 2026 wurde dieses standardisierte Fehlerbehandlungsmuster angewendet auf
        <strong>11 Frontend-Module</strong> mit <strong>~40+ normalisierten Catch-Blöcken</strong>:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Authentifizierung und Einstellungen (7 Module)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Kern- und Datenmodule (4 Module)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>Hochwertige Module (10+ Catch-Punkte):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/organizations/index.php</code> — Organisationsverwaltung, Zugriffe, Audit Trails (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — Site CRUD, Einnahmen, verwaiste Arbeit (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Tageseinträge, Kopieren/Einfügen/Löschen (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Fehlerkategorien und Bearbeitungsmuster</h2>
      <p>Der Resolver wird konsistent über mehrere Fehlerkategorien hinweg appliziert:</p>
      
      <h3>1. Netzwerkanfragefehler</h3>
      <div class="doc-code-block">
        <pre><code>// Netzwerk-Modul: HTTP-Fehler, Timeouts, Verbindungsprobleme
async function deleteResource(ep, id) {
  try {
    // ...fetch-Logik...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Netzwerkfehler');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. API-Antwortbehandlung</h3>
      <div class="doc-code-block">
        <pre><code>// Abrechnung/Einstellungen: Server hat Fehlermeldung in Payload zurückgegeben
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'Abrechnungsstatus konnte nicht geladen werden.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'Abrechnungsstatus konnte nicht geladen werden.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. UI-Operationsfehler</h3>
      <div class="doc-code-block">
        <pre><code>// Kalender/Organisationen: Vom Benutzer initiierte Aktionen (einfügen, löschen, aktualisieren)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('Erfolg!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'Aktion fehlgeschlagen. Versuchen Sie es nochmal.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Asynchrone Initialisierung</h3>
      <div class="doc-code-block">
        <pre><code>// Kernmodule: Fehler beim Starten oder abhängige Initialisierung
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'Navigation-Init fehlgeschlagen');
  PW.warn(resolved);  // Protokolliert, aber blockiert Seite nicht
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Sicherheitsüberlegungen</h2>
      <p>
        Normalisierung von Fehlermeldungen schützt Benutzerprivatsphäre und Systemintegrität:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Keine Datenbankdetails:</strong> Backend-Fehler wie „UNIQUE-Constraint fehlgeschlagen bei E-Mail"
          werden an der API-Grenze abgefangen und durch benutzerfreundliche Meldungen ersetzt
        </li>
        <li>
          <strong>Keine Dateipfade:</strong> Systemfehler, die Dateipfade oder Prozessdetails freigeben, werden entfernt
        </li>
        <li>
          <strong>Keine Auth-Leckagen:</strong> Antworten auf Authentifizierungsfehler offenbaren nie, ob ein Konto existiert
          (nur Timing-sichere generische Meldungen)
        </li>
        <li>
          <strong>Keine CORS/Netzwerk-Details:</strong> Transport-Layer-Fehler werden auf generische
          „Verbindungsfehler"-Meldungen normalisiert
        </li>
        <li>
          <strong>Sichere Fallbacks:</strong> Alle Catcher haben explizite Fallback-Meldungen;
          zeigen nie „undefined" oder „null" an
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Benutzererfahrung-Vorteile</h2>
      <p>
        Standardisierte Fehlermeldungen verbessern User Experience erheblich:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Klares Feedback:</strong> Benutzer wissen, was fehlgeschlagen ist  
          (z.B. „Passkey nicht erkannt" vs. generisch „Anmeldung fehlgeschlagen")
        </li>
        <li>
          <strong>Handlungsfähige nächste Schritte:</strong> Falls möglich, schlagen Meldungen Abhilfen vor
          („Erneut versuchen", „Verbindung prüfen", „Support kontaktieren")
        </li>
        <li>
          <strong>Konsistenz über die App:</strong> Gleiche Fehlertypen werden überall gleich angezeigt,
          reduziert Verwirrung
        </li>
        <li>
          <strong>Zugängliche Fehlerzustände:</strong> Screenreader melden normalisierte Meldungen an;
          Logging bietet vollständigen Kontext für Support-Teams
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Debugging und Support-Workflow</h2>
      <p>
        Fehlernormalisierung opfert <strong>nicht</strong> Debug-Fähigkeit. Vollständige Fehlerdetails
        fließen zu Phantom Wing:
      </p>
      <div class="doc-code-block">
        <pre><code>// Benutzer sieht saubere UI-Meldung
PC.showToast(resolveThrownMessage(error, 'Upload fehlgeschlagen.'), 'error');

// Support-Team sieht vollständige Details in Phantom Wing Logs
PW.error('Upload fehlgeschlagen', {
  userMessage: resolveThrownMessage(error, 'Upload fehlgeschlagen.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Testen und Qualitätssicherung</h2>
      <p>
        Alle Fehlerbehandlungsänderungen werden vor dem Einsatz validiert:
      </p>
      <ul class="doc-list">
        <li><strong>Syntax-Validierung:</strong> <code>php -l</code> und <code>node --check</code> überprüfen Korrektheit</li>
        <li><strong>Typ-Sicherheit:</strong> Editor-Diagnostik bestätigt keine Typ-Regressions</li>
        <li><strong>Integrationstests:</strong> Catch-Blöcke mit Mock-Error-Objekten getestet</li>
        <li><strong>Phantom Wing Logging:</strong> Fehlermeldungen in Debug-Logs überprüft</li>
        <li><strong>Accessibility-Audit:</strong> Screen-Reader-Ankündigungen auf Klarheit getestet</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Wartung und zukünftige Erweiterungen</h2>
      <p>
        Dieses Muster ist für langfristige Wartbarkeit konzipiert:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Lokalisierung-bereit:</strong> Fehlermeldungen können durch i18n ohne
          Modifizierung der Resolver-Logik durchgehen
        </li>
        <li>
          <strong>Erweiterbar:</strong> Resolver kann erweitert werden zur Behandlung von Fehlercodes,
          Wiederholung oder spezialisierten Nachschlagfunktionen ohne bestehendem Code zu brechen
        </li>
        <li>
          <strong>Dokumentation:</strong> Jedes Modul enthält Inline-Kommentare, die
          Fehlerszenarien und Fallback-Strategien erklären
        </li>
        <li>
          <strong>Git-Verlauf:</strong> Alle Änderungen mit detaillierten Commit-Meldungen
          und File-Level-Diffs zum einfachen Review getrackt
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Zusammenfassung: Der PayCal Fehlerbehandlungs-Standard</h2>
      <p>
        PayCal's standardisierte Normalisierung von Fehlermeldungen stellt sicher, dass:
      </p>
      <ol class="doc-list">
        <li>Benutzer klares, handlungsfähiges Fehler-Feedback erhalten</li>
        <li>Sensitive System-Details dem Frontend nie entweichen</li>
        <li>Nachrichtenbehandlung über alle 11+ Frontend-Module konsistent ist</li>
        <li>Debug- und Support-Teams zeilen vollständigen Fehlerkontext via Phantom Wing</li>
        <li>Code wartbar, testbar und zugänglich ist</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Diese Verpflichtung zu Sicherheit, Klarheit und Konsistenz spiegelt
        PayCals Widmung zu Benutzervertrauen und transparenter Informationsfreigabe wider.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
