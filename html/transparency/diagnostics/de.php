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
  'TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Optionale Diagnose &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      PayCal enthält eine optionale Diagnoseschicht, die Sie steuern. Hier erfahren Sie genau,
      was sie erfasst, was auf Ihrem Gerät bleibt und wie sie verwendet wird.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Übersicht</h2>
      <p>
        PayCal wird mit einer integrierten Diagnoseschicht namens <strong>Phantom Wing</strong>
        geliefert. Standardmäßig ist sie fast vollständig still — sie erfasst nur schwerwiegende,
        nicht behandelte Fehler und sendet niemals etwas ohne Ihre ausdrückliche Aktivierung.
      </p>
      <p>
        Wenn Sie auf ein Problem stoßen und dem Support mehr Kontext mitteilen möchten, können
        Sie zusätzliche Diagnosen unter
        <a href="/settings/diagnostics/">Einstellungen → Debugging (Optional)</a> aktivieren.
        Jede Einstellung ist unabhängig; Sie können nur die relevante aktivieren.
        Alle drei sind standardmäßig <strong>Deaktiviert</strong>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Die drei Opt-in-Steuerungen</h2>
      <p>
        Jede Steuerung befindet sich im Bereich <strong>Debugging (Optional)</strong> am unteren
        Rand Ihrer Einstellungsseite. Sie sind nur zur Fehlerbehebung gedacht — das Aktivieren
        kann Seiteninteraktionen leicht verlangsamen, da im Browser zusätzliche Arbeit anfällt.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Einstellung</th>
            <th>Was sie aktiviert</th>
            <th>Wer es sieht</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Konsolennachrichten</strong></td>
            <td>
              Gibt Warnungen, Informationsprotokoll und Leistungsmarkierungen in der
              Entwicklerkonsole Ihres Browsers aus. Nützlich zur Selbstdiagnose — öffnen Sie
              die DevTools und suchen Sie nach Nachrichten mit dem Präfix <code>[PayCal]</code>
              oder Emoji-Markierungen.
            </td>
            <td>Nur Sie (Ihre Browserkonsole, nie übertragen)</td>
          </tr>
          <tr>
            <td><strong>Detaillierte Diagnose</strong></td>
            <td>
              Aktiviert die schrittweise interne Ereignisprotokollierung. Phantom Wing erfasst
              den vollständigen Lebenszyklus von Operationen (Kalenderladevorgänge,
              Formularübermittlungen, Sitzungsereignisse) in einem In-Memory-Protokoll,
              das in jeden Support-Bericht aufgenommen wird, den Sie teilen möchten.
            </td>
            <td>Nur Sie, es sei denn, Sie teilen einen Support-Bericht</td>
          </tr>
          <tr>
            <td><strong>Netzwerk-Einblicke</strong></td>
            <td>
              Protokolliert API-Anforderungszeiten — wie lange jeder Server-Roundtrip dauert,
              Antwortgrößen und ob Batching oder Caching angewendet wurde. Hilft bei der
              Diagnose von Langsamkeit bei bestimmten Operationen.
            </td>
            <td>Nur Sie (Ihre Browserkonsole, nie übertragen)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Was Phantom Wing standardmäßig tut</h2>
      <p>
        Auch wenn alle drei Steuerungen deaktiviert sind, führt Phantom Wing einen leichtgewichtigen
        Basismonitor aus, der nur schwerwiegende Ausfälle erfasst:
      </p>
      <ul class="doc-list">
        <li>Nicht abgefangene JavaScript-Ausnahmen (<code>window.onerror</code>)</li>
        <li>Nicht behandelte Promise-Ablehnungen</li>
        <li>Fetch-Aufrufe, die mit einem Netzwerkfehler scheitern (keine HTTP-Fehler — diese werden pro Funktion behandelt)</li>
      </ul>
      <p>
        Diese Basisdaten verbleiben vollständig im Speicher und werden niemals übertragen.
        Sie werden als Einsekundenzusammenfassung in der Browserkonsole beim Seitenladen angezeigt,
        damit Sie schnell sehen können, ob etwas schiefgelaufen ist, und dann verworfen.
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
      <h2>Phantom Wing &amp; Telemetrie</h2>
      <p>
        Phantom Wing verfügt über einen leichtgewichtigen Telemetriekanal, der zur aggregierten
        Messung der Funktionszuverlässigkeit verwendet wird — z. B. um festzustellen, ob eine
        bestimmte Operation auf der Plattform mit ungewöhnlicher Häufigkeit fehlschlägt.
      </p>
      <h3>Was die Telemetrie sendet</h3>
      <ul class="doc-list">
        <li>Anonymisierte Ereignisanzahlen stündlich zusammengefasst (z. B. <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Fehlerkategorie und -typ — niemals die vollständige Fehlermeldung oder den Stack-Trace</li>
        <li>Keine Benutzeridentifikatoren, keine Sitzungstoken, keine IP-Adressen</li>
      </ul>
      <h3>Was die Telemetrie niemals sendet</h3>
      <ul class="doc-list">
        <li>Ihren Namen, Ihre E-Mail oder Kontodetails</li>
        <li>Einkommen, Lohnzeitraum oder Finanzdaten</li>
        <li>Vollständige Fehlermeldungen oder Stack-Traces</li>
        <li>URL-Pfade oder Abfragezeichenfolgen</li>
        <li>Tastenanschläge oder Formularfeldwerte</li>
      </ul>
      <h3>Ratenbegrenzung &amp; Rückzug</h3>
      <p>
        Telemetrie-Übermittlungen sind serverseitig pro Benutzer pro Minute begrenzt. Wenn Ihr
        Client den Schwellenwert überschreitet, bestätigt der Server dies still und verwirft den
        Überschuss — nichts wird gespeichert. Der Client wendet auch exponentiellen Rückzug an:
        Nach zwei aufeinanderfolgenden serverseitigen Fehlern deaktiviert er die
        Telemetrie-Übermittlung automatisch für zehn Minuten.
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
      <h2>Datenschwärzung</h2>
      <p>
        Bevor ein Wert im Speicher abgelegt oder über Telemetrie übertragen wird, wendet
        Phantom Wing einen automatischen Schwärzungsdurchlauf an. Werte, die bekannten
        sensiblen Mustern entsprechen, werden durch <code>[REDACTED]</code> ersetzt:
      </p>
      <ul class="doc-list">
        <li>E-Mail-Adressen</li>
        <li>Bearer-Token und Autorisierungs-Header-Werte</li>
        <li>CSRF-Token</li>
        <li>Zeichenfolgen, die wie kryptografische Schlüssel oder base64-codierte Blobs über einer Mindestlänge aussehen</li>
      </ul>
      <p>
        Die Schwärzung wird auf alle Argumente angewendet, die an abgefangene Konsolenmethoden
        übergeben werden, und auf alle Telemetrie-Feldwerte vor der Warteschlange. Sie kann nicht
        durch aktivierte Diagnoseeinstellungen umgangen werden.
      </p>
    </section>

    <section class="doc-section">
      <h2>Bereichswächter: Seiten, auf denen Diagnosen unterdrückt werden</h2>
      <p>
        Die Telemetrie-Übermittlung wird auf Authentifizierungsseiten (<code>/auth/</code>)
        vollständig unterdrückt. Das bedeutet, dass selbst wenn Netzwerk-Einblicke aktiviert
        sind, während Sie sich in den Anmelde-, Registrierungs- oder Wiederherstellungsabläufen
        befinden, keine Telemetrie übertragen wird. Dies ist eine Defense-in-Depth-Maßnahme,
        um jede Möglichkeit zu verhindern, dass anmeldenahe Daten in Diagnosekanälen erscheinen.
      </p>
    </section>

    <section class="doc-section">
      <h2>Ihre Kontrolle</h2>
      <p>
        Alle drei Diagnoseeinstellungen werden als Kontoeinstellungen gespeichert, nicht als
        Browser-Cookies. Sie folgen Ihrem Konto auf allen Geräten und Sitzungen und sind
        standardmäßig für jedes Konto — einschließlich neuer Konten — <strong>Deaktiviert</strong>.
        Sie können sie jederzeit unter
        <a href="/settings/diagnostics/">Einstellungen → Debugging (Optional)</a> ändern.
      </p>
      <p>
        Das Deaktivieren einer Einstellung tritt beim nächsten Seitenladen sofort in Kraft.
        Zwischen Sitzungen werden keine Diagnosedaten aufbewahrt: Das In-Memory-Protokoll von
        Phantom Wing wird gelöscht, wenn Sie die Seite verlassen oder den Tab schließen.
      </p>
    </section>

    <section class="doc-section">
      <h2>Zusammenfassung</h2>
      <ol class="doc-list">
        <li>Alle drei Debug-Steuerungen sind standardmäßig <strong>Deaktiviert</strong> und müssen von Ihnen ausdrücklich aktiviert werden</li>
        <li>Konsolennachrichten und Netzwerk-Einblicke verlassen niemals Ihr Gerät</li>
        <li>Detaillierte Diagnose verbleibt im Speicher und wird nur geteilt, wenn Sie einen Support-Bericht teilen möchten</li>
        <li>Telemetrie sendet nur anonymisierte, aggregierte Ereignisanzahlen — keine persönlichen Daten</li>
        <li>Alle Werte werden vor der Speicherung oder Übertragung geschwärzt, unabhängig von den Diagnoseeinstellungen</li>
        <li>Telemetrie wird auf allen Authentifizierungsseiten vollständig unterdrückt</li>
        <li>Ratenbegrenzung und automatischer Client-Rückzug verhindern versehentliche Überberichterstattung</li>
      </ol>
      <p class="doc-section-footer-note">
        Phantom Wing ist so konzipiert, dass Sie alle Diagnosen auf unbestimmte Zeit deaktiviert
        lassen können. Die Opt-in-Steuerungen existieren, um Ihnen und dem Support-Team eine
        gemeinsame Sprache zu geben, wenn etwas schiefgeht — nicht um standardmäßig Daten
        zu sammeln.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
