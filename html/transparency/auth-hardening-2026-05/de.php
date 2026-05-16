<?php
/**
 * Public Transparency: Auth, Passkey, and Redis Hardening — May 2026
 *
 * PURPOSE: Disclose all findings from the May 12, 2026 internal security audit of
 * authentication, passkey, and Redis infrastructure. Describes each flaw, its
 * risk, and exactly how it was fixed.
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
$pageTitle = 'Auth-, Passkey- und Redis-Härtung — Mai 2026 - [PayCal]';
$pageLabel = 'Auth-, Passkey- & Redis-Härtung — Mai 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Auth-, Passkey- &amp; Redis-Härtung — Mai 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Auth-, Passkey- &amp; Redis-Härtung — Mai 2026</h1>
    <p class="deck">
      Am 12. Mai 2026 führten wir ein internes Audit unserer Authentifizierungs-, Passkey- und
      Redis-Infrastruktur durch. Wir fanden elf Probleme — jedes davon in Code, den wir selbst
      geschrieben haben. Dieser Artikel dokumentiert, was wir gefunden haben, warum es wichtig
      war und was wir genau geändert haben.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Zusammenfassung</h2>
      <table class="doc-table" aria-label="Zusammenfassung der Audit-Ergebnisse">
        <tbody>
          <tr>
            <td><strong>Audit-Datum</strong></td>
            <td>12. Mai 2026</td>
          </tr>
          <tr>
            <td><strong>Umfang</strong></td>
            <td>Authentifizierung, Passkey (WebAuthn) und Redis-Infrastruktur</td>
          </tr>
          <tr>
            <td><strong>Gesamte Befunde</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Schweregrad-Aufschlüsselung</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Behebungsstatus</strong></td>
            <td>Alle Befunde im Commit <code>493d5e44</code> behoben. Vollständige Testsuite bestanden. Keine Regression.</td>
          </tr>
          <tr>
            <td><strong>Ausnutzungshinweise</strong></td>
            <td>Keine</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Warum wir das veröffentlichen</h2>
      <p>
        Wir haben diese Probleme in unserem eigenen Anwendungscode und unseren Infrastrukturschichten
        gefunden — nicht in Drittanbieter-Abhängigkeiten oder externen Diensten. Code, den wir
        überprüft, committed und ausgeliefert haben.
      </p>
      <p>
        Wir veröffentlichen dies, weil Sicherheitstransparenz mehr erfordert als die Offenlegung
        externer CVEs oder das Bestehen von Audits. Es bedeutet, öffentlich rechenschaftspflichtig
        zu sein, wenn das eigene Team Code liefert, der nicht den selbst gesetzten Standard erfüllt.
      </p>
      <p>
        Wir schämen uns nicht dafür. Der größere Fehler wäre gewesen, diese Probleme zu entdecken
        und sich zu entscheiden, sie nicht offenzulegen.
      </p>
    </section>

    <section class="doc-section">
      <h2>Audit-Methodik</h2>
      <p>
        Dieses Audit wurde intern vom Engineering-Team am 12. Mai 2026 durchgeführt. Die Überprüfung
        umfasste alle Code-Pfade im Zusammenhang mit der Authentifizierungs-Zustandsverwaltung,
        dem WebAuthn-Credential-Lebenszyklus und der Redis-Schlüsselbehandlung.
      </p>
      <ul class="doc-list">
        <li><strong>Manuelle Code-Überprüfung</strong> aller Controller-, Domain- und Infrastrukturdateien, die an Session-Erstellung, Passkey-Registrierung, Passkey-Login und Kontowiederherstellungsabläufen beteiligt sind.</li>
        <li><strong>Statische Analyse</strong> via PHPStan auf Level 9 — keine Toleranz für typunsichere oder unerreichbare Code-Pfade.</li>
        <li><strong>Bedrohungsmodellierung</strong> gegen die WebAuthn Level 2 Spezifikation (§6.1 Authentifizierdaten, §7.1 Registrierungszeremonie, §7.2 Authentifizierungszeremonie).</li>
        <li><strong>Regressionstests</strong> mit der vollständigen PHPUnit-Regressionssuite nach der Behebung. Alle Tests bestanden.</li>
      </ul>
      <p>Kein externer Prüfer, kein Bug-Bounty-Bericht oder Sicherheitsvorfall ging dieser Überprüfung voraus. Diese Probleme wurden durch einen routinemäßigen internen Prozess identifiziert.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Unsere Entwicklungsphilosophie</h2>
      <p>Dieses Audit hat Versäumnisse in drei Prinzipien aufgedeckt, die wir als grundlegend betrachten:</p>
      <ul class="doc-list">
        <li>
          <strong>Atomarität vor Korrektheit.</strong> Wenn zwei Operationen zusammen stattfinden müssen,
          behandle sie als eine Operation oder versuche das Design erst gar nicht. Ein System, das
          &ldquo;die meiste Zeit korrekt&rdquo; ist, ist nicht korrekt.
        </li>
        <li>
          <strong>Schichtweise Verteidigung.</strong> Keine einzelne Prüfung sollte die einzige Barriere
          zu einer Sicherheitsgrenze sein. Wenn die Datenbank ein Credential als gesperrt markiert,
          muss der Registrierungspfad dies ebenfalls durchsetzen. Die Verteidigung darf keine Lücken
          zwischen den Schichten haben.
        </li>
        <li>
          <strong>Informationsasymmetrie als Designziel.</strong> Ein Angreifer, der das System sondiert,
          sollte so wenig wie möglich darüber erfahren, was darin vorgeht. Fehlermeldungen,
          Protokolleinträge und Antwortzeiten sind allesamt Angriffsflächen.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Befund 1 &mdash; Nicht-atomares <code>hset + expire</code> (Redis-Race-Condition) <span class="doc-badge high">High</span></h2>
      <p><strong>Kategorie: Redis / Atomarität</strong></p>
      <p>
        An acht Aufrufstellen wurde ein Redis-Hash mit <code>HSET</code> geschrieben und dann
        sofort mit einem separaten <code>EXPIRE</code>-Befehl ein TTL gesetzt:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        Das sind zwei separate Roundtrips zu Redis. Wenn der PHP-Prozess abstürzt, unterbrochen
        wird, einen Timeout erreicht oder Redis zwischen ihnen einen momentanen Fehler erfährt,
        wird der Hash ohne Ablaufzeit geschrieben — und lebt für immer in Redis.
      </p>
      <p>Die betroffenen Aufrufstellen und ihre Sicherheitsimplikationen:</p>
      <table class="doc-table" aria-label="Betroffene Aufrufstellen für nicht-atomares hset+expire">
        <thead>
          <tr>
            <th scope="col">Aufrufstelle</th>
            <th scope="col">Schlüsseltyp</th>
            <th scope="col">Folge fehlenden TTL</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Session-Datensatz</td>
            <td>Session läuft nie ab — Konto über die beabsichtigte Lebensdauer hinaus zugänglich</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (Registrierungs-Challenge)</td>
            <td>WebAuthn-Challenge</td>
            <td>Veraltete Challenge-Daten bleiben über die beabsichtigte Lebensdauer erhalten, erhöhen das Wiederholungsrisiko</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (Register-Challenge)</td>
            <td>WebAuthn-Challenge</td>
            <td>Wie oben</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (Login-Challenge)</td>
            <td>WebAuthn-Challenge</td>
            <td>Wie oben</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Wiederherstellungs-Passkey-Challenge</td>
            <td>Wiederherstellungs-Sitzungsdaten laufen nie ab</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (Code-Ausgabe)</td>
            <td>Wiederherstellungs-E-Mail-Code</td>
            <td>Einmalcodes überleben ihr beabsichtigtes Ablaufzeitfenster</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (Code-Neuversand)</td>
            <td>Wiederherstellungs-E-Mail-Code</td>
            <td>Wie oben</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Admin-Einmal-Tokens</td>
            <td>Tokens, die in 5 Minuten ablaufen sollen, können auf unbestimmte Zeit bestehen</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Wiederherstellungs-Transaktionsdatensatz</td>
            <td>Wiederherstellungs-Transaktionszustand wird nie bereinigt</td>
          </tr>
        </tbody>
      </table>
      <p>
        Für Sessions ist dies eine direkte Verletzung der Zugriffslebensdauer. Eine Session sollte
        eine harte Obergrenze haben. Wenn das TTL nie gesetzt wird, existiert diese Obergrenze nicht.
      </p>
      <p>
        Bei Einmal-Capability-Tokens kann ein Token, der genau 300 Sekunden gültig sein soll,
        noch Tage später gültig sein.
      </p>
      <p><strong>Die Lösung:</strong> Wir haben <code>Database::hsetex()</code> eingeführt — ein Wrapper, der
      beide Operationen innerhalb einer Redis <code>MULTI/EXEC</code>-Transaktion ausführt und sie so atomar macht.
      Die Operationen werden in derselben Ausführungseinheit ausgeführt, sodass der Schlüssel nicht ohne sein TTL
      existieren kann. Der Schlüssel hat entweder Daten und ein TTL oder gar nichts.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Jede Aufrufstelle, die ein <code>hset</code> gefolgt von <code>expire</code> für denselben Schlüssel ausgab, wurde konvertiert.</p>
    </section>

    <section class="doc-section">
      <h2>Befund 2 &mdash; Abmeldung und CSRF-Invalidierung konnten lautlos scheitern <span class="doc-badge high">High</span></h2>
      <p><strong>Kategorie: Redis / Abmeldung, CSRF</strong></p>
      <p>
        Die Methode <code>Database::del()</code> — zuständig für das Löschen von Redis-Schlüsseln
        nach Muster — enumerierte Schlüssel über das <em>Lesereplikat</em> und gab dann
        <code>DEL</code>-Befehle an das <em>Primär</em>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        Redis-Replikation ist asynchron. Wenn das Replikat zurückliegt — selbst um Millisekunden —
        enthält es möglicherweise noch nicht den Schlüssel, der gerade geschrieben wurde. In diesem
        Fall gibt <code>keys()</code> eine leere Liste zurück und kein <code>DEL</code> wird an
        das Primär gesendet. Der Schlüssel überlebt.
      </p>
      <p>Die zwei kritischsten Aufrufer von <code>del()</code>:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — Abmeldung:</strong> Wenn sich ein Benutzer abmeldet,
          löschen wir seinen Session-Schlüssel. Wenn das Replikat zurückliegt, gibt die Session-Schlüsselliste
          leer zurück, das Löschen wird nie ausgelöst, und die Session existiert weiterhin auf dem Primär.
          Der Benutzer glaubt, abgemeldet zu sein. Er ist es nicht.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — Nonce-Invalidierung:</strong> CSRF-Tokens sind
          Einmal-Nonces. Nach der ersten Verwendung müssen sie gelöscht werden. Wenn das Löschen nie
          ausgelöst wird, kann der Token bei einer zweiten Anfrage wiederverwendet werden. Einmalig
          wird wiederverwendbar.
        </li>
      </ul>
      <p>
        Dieser Fehler ist subtil, weil er sich nur unter Last oder bei vorübergehender Replikatverzögerung
        manifestiert. In der Entwicklung gegen eine einzelne Redis-Instanz wird er nie ausgelöst.
      </p>
      <p><strong>Die Lösung:</strong> Schlüsselenumeration und Löschung müssen dieselbe Instanz als Ziel haben.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Befund 3 &mdash; WebAuthn-Benutzerverifizierungs-Bypass <span class="doc-badge high">High</span></h2>
      <p><strong>Kategorie: Authentifizierung</strong></p>
      <p>
        In <code>AccountRecoveryController</code> wurde beim Registrieren eines Passkeys im Rahmen
        der Kontowiederherstellung <code>false</code> für <code>requireUserVerification</code> übergeben:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — UV not enforced on verification
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    false,  // requireUserVerification — should be true
    true
);</code></pre>
      </div>
      <p>
        Die an den Client gesendete Challenge spezifizierte <code>userVerification: 'required'</code> —
        dem Authentifikator wurde mitgeteilt, dass der Benutzer eine biometrische Prüfung oder PIN
        abschließen muss. Aber bei der Überprüfung der Antwort sagten wir der Bibliothek, das UV-Flag
        nicht durchzusetzen.
      </p>
      <p>
        Ein modifizierter Client könnte eine Authentifikator-Antwort mit gelöschtem UV-Bit senden.
        Unser Server würde sie akzeptieren, ohne zu verlangen, dass die biometrische Verifizierung
        tatsächlich stattgefunden hat.
      </p>
      <p>
        Der Kontowiederherstellungsfluss ist der Weg, den ein Benutzer nimmt, wenn er den Zugang zu
        seinen anderen Credentials verloren hat. Dies ist die höchstriskante Authentifizierungsfläche,
        die wir betreiben. Die biometrische Durchsetzung hier zu schwächen ist genau der falsche
        Kompromiss.
      </p>
      <p><strong>Die Lösung:</strong> UV wird jetzt durchgesetzt. Eine Antwort, bei der die Authentifizierdaten das UV-Flag nicht gesetzt tragen, wird abgelehnt.</p>
      <div class="doc-code-block">
        <pre><code>// After — UV enforced
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    true,   // requireUserVerification — enforced
    true
);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Befund 4 &mdash; Signaturzähler-Klonerkennung übersah Wiederholungsangriffe <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorie: Authentifizierung</strong></p>
      <p>Unsere Passkey-Klonerkennung prüfte:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        Die WebAuthn Level 2 Spezifikation (§6.1) besagt: Wenn der gespeicherte Signaturzähler
        ungleich Null ist und der neue Signaturzähler nicht <em>strikt größer</em> als der gespeicherte
        Wert ist, sollte das Credential als möglicherweise geklont betrachtet werden. Unsere Bedingung
        erforderte <code>&lt;</code>, nicht <code>&lt;=</code>, daher passierte ein gleicher Zähler
        — wie bei einem Wiederholungsangriff — ohne das Klon-Flag auszulösen.
      </p>
      <p><strong>Die Lösung:</strong> An die Spezifikation angepasst.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Befund 5 &mdash; Signaturzähler wurde nicht immer persistiert <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorie: Authentifizierung</strong></p>
      <p>Nach einem erfolgreichen Passkey-Login war das Signaturzähler-Update davon abhängig, dass er ungleich Null war:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Manche Authentifikatoren geben <code>0</code> als Sentinel zurück, was bedeutet: &ldquo;Dieses
        Gerät implementiert keinen Zähler.&rdquo; Wenn ein Gerät später beginnt, einen echten Zähler
        zurückzugeben (Firmware-Update oder der Benutzer registriert dasselbe Credential auf einer
        Zähler-unterstützenden Plattform), würden wir den anfänglichen echten Zähler nie persistieren,
        weil wir für immer <code>0</code> gespeichert hatten.
      </p>
      <p>
        Die Klonerkennung (Befund 4) erfordert, dass der gespeicherte Zähler ungleich Null ist — ein
        Authentifikator, den wir dauerhaft als <code>0</code> markieren, ist dauerhaft von der
        zählerbasierten Sicherung ausgeschlossen.
      </p>
      <p><strong>Die Lösung:</strong> Der Signaturzähler wird immer geschrieben. Der Klonerkennung-Schwellenwert übernimmt die Interpretation.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Befund 6 &mdash; Gesperrter Passkey konnte neu registriert werden <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorie: Authentifizierung</strong></p>
      <p>
        Wenn ein Credential als gesperrt markiert wurde (Klonerkennung ausgelöst), gab es im
        Registrierungspfad keine Prüfung, die die Neuregistrierung derselben <code>credential_id</code>
        verhinderte. Ein Angreifer mit dem rohen Passkey-Credential und Kontozugang könnte das
        gesperrte Credential neu registrieren und seine kompromittierte Historie löschen.
      </p>
      <p>
        Sperrung ist nur dann bedeutsam, wenn sie dauerhaft ist. Wenn sie durch Neuregistrierung mit
        demselben Credential überschrieben werden kann, bietet die Klonerkennung keinen dauerhaften Schutz.
      </p>
      <p><strong>Die Lösung:</strong> Wenn <code>revoked_at</code> auf einem vorhandenen Credential-Datensatz nicht leer ist,
      wird die Neuregistrierung mit HTTP 403 blockiert und ein Sicherheitsprotokolleintrag wird geschrieben.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Befund 7 &mdash; Kontoaufzählung durch unterschiedliche Fehlerantworten <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorie: Informationsoffenlegung</strong></p>
      <p>
        Wenn ein Passkey-Login mit einer unbekannten E-Mail versucht wurde, hatte der Fehlerantwort-Body
        eine andere Form als bei anderen Fehlerfällen — ein leeres <code>[]</code>-Daten-Payload
        versus den <code>{'error': 'passkey_invalid'}</code>-Body, der anderswo zurückgegeben wird.
        Ein Client, der die API sondiert, könnte &ldquo;diese E-Mail hat kein Konto&rdquo; von
        &ldquo;diese E-Mail existiert, aber der Challenge ist gescheitert&rdquo; durch Inspektion
        des Antwort-Bodys unterscheiden.
      </p>
      <p>
        Zusätzlich wurde die rohe E-Mail-Adresse ins Observability-Protokoll geschrieben.
        Protokollaggregations-Pipelines sollten niemals rohe Benutzer-E-Mail-Adressen halten —
        wenn das Protokollsystem kompromittiert wird, wird jeder Aufzählungsversuch zu einer
        Liste von E-Mail-Adressen.
      </p>
      <p><strong>Die Lösung:</strong> Sowohl &ldquo;E-Mail nicht gefunden&rdquo; als auch &ldquo;keine Credentials
      registriert&rdquo; geben jetzt denselben Fehler-Body zurück. Das Observability-Protokoll zeichnet
      nur einen SHA-256-Hash der E-Mail auf — ausreichend für die Incident-Korrelation, unzureichend
      zur Rekonstruktion der Adresse.</p>
      <div class="doc-code-block">
        <pre><code>// Before
Lens::add('[PASSKEY] Login email not found', ['email' => $email]);
Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);

// After
Lens::add('[PASSKEY] Login email not found', ['email_hash' => hash('sha256', $email)]);
Response::error('Authentication failed.', ['error' => 'passkey_invalid'], HttpStatus::HTTP_UNAUTHORIZED);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Befund 8 &mdash; Wiederherstellungsschlüssel-DB-Zustand vor Bestätigung der E-Mail-Zustellung geschrieben <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorie: Datenintegrität</strong></p>
      <p>
        Bei der Generierung von Kontowiederherstellungsschlüsseln schrieb der Server
        <code>recovery_key_generated = 1</code> und <code>recovery_proof_key</code> in den
        Benutzerdatensatz <em>bevor</em> die Wiederherstellungsschlüssel-E-Mail gesendet wurde:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — DB written first, email second
Database::hset(Keys::USER.':'.$user->user_uuid, [
    'recovery_key_generated' => '1',
    'recovery_proof_key' => $recoveryProofKey,
]);
$sent = EmailGarum::sendRecoveryKeyEmail(...);</code></pre>
      </div>
      <p>
        Wenn die E-Mail nicht gesendet werden konnte, würde die Datenbank <code>recovery_key_generated = 1</code>
        anzeigen — das System glaubt, ein Schlüssel wurde ausgestellt. Der Benutzer hat ihn nie erhalten.
      </p>
      <p>
        Es gibt keinen Regenerierungspfad für einen Benutzer in diesem Zustand. Die Kontowiederherstellung
        ist für dieses Konto dauerhaft defekt, bis eine manuelle Intervention erfolgt.
      </p>
      <p><strong>Die Lösung:</strong> Die E-Mail-Zustellung wird zuerst bestätigt. Der Datenbankzustand spiegelt wider, was tatsächlich passiert ist.</p>
      <div class="doc-code-block">
        <pre><code>// After — email first, then persist
$sent = EmailGarum::sendRecoveryKeyEmail(...);
if ($sent) {
    Database::hset(Keys::USER.':'.$user->user_uuid, [
        'recovery_key_generated' => '1',
        'recovery_proof_key' => $recoveryProofKey,
    ]);
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Befund 9 &mdash; Deaktivierter Registrierungspfad sammelte weiterhin Passwortfelder <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategorie: Angriffsfläche</strong></p>
      <p>
        <code>RegistrationController</code> las noch immer <code>password</code> und
        <code>confirm_password</code> aus POST, obwohl die passwortbasierte Registrierung deaktiviert
        wurde. Die PayCal-Registrierung ist ausschließlich Passkey-basiert.
      </p>
      <p>
        Das Sammeln von Feldern, die keinen Zweck erfüllen, ist nicht harmlos. Jeder aus der
        Benutzereingabe gelesene Wert ist eine Angriffsfläche: Er kann protokolliert, geprüft,
        versehentlich an andere Funktionen weitergegeben oder in Fehler-Payloads aufgenommen werden.
        Das Prinzip der minimalen Angriffsfläche erfordert, dass wir nicht sammeln, was wir nicht
        verwenden.
      </p>
      <p><strong>Die Lösung:</strong> Die beiden Felder wurden aus der Eingabe-Sammlungskarte entfernt.</p>
    </section>

    <section class="doc-section">
      <h2>Befund 10 &mdash; Benutzer-E-Mail in der 403-Antwort zur E-Mail-Verifizierung <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategorie: Informationsoffenlegung</strong></p>
      <p>
        <code>EmailVerificationGuard</code> — das Middleware, das die E-Mail-Verifizierung vor
        dem Gewähren des Zugriffs auf geschützte Ressourcen durchsetzt — enthielt <code>user_email</code>
        im 403-Antwort-Body:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        Wenn ein Angreifer einen gültigen, aber unverififizierten Session-Token erhält (durch
        Session-Fixierung oder einen kompromittierten temporären Link), kann er die mit dem Konto
        verknüpfte E-Mail-Adresse aus dem 403-Antwort-Body erfahren — ohne die E-Mail selbst
        angegeben zu haben. Die einzige Partei, die von der E-Mail in diesem Fehler-Payload
        profitiert, ist jemand, der den Session-Token, aber nicht die E-Mail hat.
      </p>
      <p><strong>Die Lösung:</strong> Das E-Mail-Feld wurde aus dem Fehler-Payload entfernt.</p>
    </section>

    <section class="doc-section">
      <h2>Befund 11 &mdash; Toter Code in <code>EmailGarum::verifyNewUserEmail()</code> <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategorie: Toter Code / Angriffsfläche</strong></p>
      <p>
        <code>EmailGarum</code> enthielt eine 90-zeilige Methode, <code>verifyNewUserEmail()</code>,
        die einen passwortbasierten E-Mail-Änderungsfluss handhabte. Dieser Fluss wurde ersetzt, als
        die Plattform auf ausschließlich Passkey-basierte Authentifizierung umstellte. Die Methode
        wurde nirgendwo in der Codebase aufgerufen.
      </p>
      <p>
        Toter Code ist nicht neutral. Er belegt Platz auf der Sicherheitsprüfungsfläche, in der
        statischen Analyse und in der kognitiven Last aller, die die Datei lesen. Er stellt auch ein
        Risiko dar, dass ein zukünftiger Entwickler, der nicht weiß, dass er intentional aufgegeben
        wurde, ihn ohne vollständigen Kontext in einen neuen Fluss verdrahten könnte.
      </p>
      <p><strong>Die Lösung:</strong> Gelöscht. Alle Aufrufstellen wurden vor der Entfernung als leer bestätigt.</p>
    </section>

    <section class="doc-section">
      <h2>Übersicht aller Befunde</h2>
      <table class="doc-table" aria-label="Übersicht aller Befunde">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Befund</th>
            <th scope="col">Schweregrad</th>
            <th scope="col">Kategorie</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td>Nicht-atomares <code>hset + expire</code> an 9 Aufrufstellen</td><td><span class="doc-badge high">High</span></td><td>Redis / Atomarität</td></tr>
          <tr><td>2</td><td><code>del()</code> verwendet Lesereplikat für Schlüsselenumeration</td><td><span class="doc-badge high">High</span></td><td>Redis / Abmeldung, CSRF</td></tr>
          <tr><td>3</td><td>WebAuthn UV-Bypass bei der Kontowiederherstellungs-Registrierung</td><td><span class="doc-badge high">High</span></td><td>Authentifizierung</td></tr>
          <tr><td>4</td><td>Signaturzähler-Klonerkennung übersah Wiederholungsangriffe</td><td><span class="doc-badge medium">Medium</span></td><td>Authentifizierung</td></tr>
          <tr><td>5</td><td>Signaturzähler nicht persistiert, wenn Authentifikator Null zurückgibt</td><td><span class="doc-badge medium">Medium</span></td><td>Authentifizierung</td></tr>
          <tr><td>6</td><td>Gesperrter Passkey konnte neu registriert werden</td><td><span class="doc-badge medium">Medium</span></td><td>Authentifizierung</td></tr>
          <tr><td>7</td><td>Kontoaufzählung via Fehler-Body + rohe E-Mail in Protokollen</td><td><span class="doc-badge medium">Medium</span></td><td>Informationsoffenlegung</td></tr>
          <tr><td>8</td><td>Wiederherstellungsschlüssel-DB-Zustand vor E-Mail-Bestätigung geschrieben</td><td><span class="doc-badge medium">Medium</span></td><td>Datenintegrität</td></tr>
          <tr><td>9</td><td>Deaktivierte Registrierung sammelte weiterhin Passwortfelder</td><td><span class="doc-badge low">Low</span></td><td>Angriffsfläche</td></tr>
          <tr><td>10</td><td>Benutzer-E-Mail in der 403-Antwort zur E-Mail-Verifizierung</td><td><span class="doc-badge low">Low</span></td><td>Informationsoffenlegung</td></tr>
          <tr><td>11</td><td>Tote Methode <code>verifyNewUserEmail()</code> in EmailGarum</td><td><span class="doc-badge low">Low</span></td><td>Toter Code</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>Was wir richtig gemacht haben</h2>
      <p>Im Interesse eines vollständigen Bildes — die Grundlagen, die bereits vorhanden waren:</p>
      <ul class="doc-list">
        <li>
          <strong>Passkey-first-Authentifizierung.</strong> Die Plattform läuft auf WebAuthn ohne
          Passwort-Fallback für Passkey-Benutzer. Der UV-Bypass und die Klonerkennung-Probleme waren
          Defekte innerhalb einer grundlegend soliden Architektur.
        </li>
        <li>
          <strong>Einmal-Capability-Tokens.</strong> Admin-Level-Mutationen erforderten bereits frische,
          zeitlich begrenzte Tokens. Die Atomarität-Lösung härtete eine bestehende Sicherung, anstatt
          eine fehlende hinzuzufügen.
        </li>
        <li>
          <strong>Signiertes Sicherheitsprotokoll.</strong> Jedes Sicherheitsereignis — einschließlich
          der neuen <code>passkey_revoked_reregistration_blocked</code>-Ereignisse, die in diesem Commit
          hinzugefügt wurden — wird in ein signiertes, nur-anhängendes Protokoll mit strukturierten
          Feldern geschrieben.
        </li>
        <li>
          <strong>PHPStan auf Level 9.</strong> Alle 11 modifizierten Dateien wurden bei maximaler
          statischer Analysestrenge validiert. Die vollständige Regressionssuite bestand ohne Regression.
        </li>
        <li>
          <strong>Klonerkennung existierte.</strong> Die Logik war vorhanden und teilweise korrekt.
          Befund 4 war ein Randbedingungs-Fehler, kein fehlendes Feature.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Kundenauswirkungen</h2>
      <ul class="doc-list">
        <li><strong>Keine Ausnutzungshinweise.</strong> Alle Befunde wurden intern durch routinemäßige Code-Überprüfung identifiziert. Kein externer Bericht, CVE oder Vorfall ging dieser Offenlegung voraus.</li>
        <li><strong>Keine Klartextexposition von Credentials.</strong> Keine Passwörter oder Wiederherstellungsschlüssel wurden exponiert. Credential-Daten im Ruhezustand bleiben verschlüsselt. Biometrische Daten verlassen niemals das Authentifikator-Gerät und werden niemals von PayCal übertragen oder gespeichert.</li>
        <li><strong>Keine Hinweise auf unbefugten Kontozugriff.</strong> Sicherheitsprotokolle zeigen keine anomalen Muster, die mit einer Ausnutzung dieser Vektoren konsistent wären.</li>
        <li><strong>Alle Befunde vor der Offenlegung behoben.</strong> Jedes in diesem Artikel beschriebene Problem wurde behoben, committed und getestet, bevor diese Seite veröffentlicht wurde.</li>
        <li><strong>Vollständige Regressionssuite bestanden.</strong> Vollständige PHPUnit-Suite und PHPStan Level 9 statische Analyse nach der Behebung sauber abgeschlossen.</li>
        <li><strong>Überwachung erweitert.</strong> Neue Sicherheitsprotokoll-Ereignisse wurden für die Durchsetzung der Passkey-Sperrung (Befund 6) hinzugefügt, um zukünftige Anomalien früher aufzudecken.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Prävention und Wiederholungsschutz</h2>
      <p>Zwei als ständige Richtlinie ab diesem Audit übernommene Entwicklungsregeln:</p>
      <div class="subject-example-cutout" role="note" aria-label="Neue Entwicklungsregel: hsetex als Standard-Redis-Schreibmuster">
        <h3><code>hsetex</code> ist das Standard-Redis-Schreibmuster</h3>
        <p>
          Jeder zukünftige Code, der einen Hash mit einem TTL schreiben muss, muss
          <code>Database::hsetex()</code> verwenden. Das alte zweistufige Muster ist nicht mehr
          erlaubt. PHPStan-Regeln werden geschrieben, um neue Vorkommen zu kennzeichnen.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="Neue Entwicklungsregel: Schreibinstanz-Vorrang für alle Schlüsseloperationen">
        <h3>Schreibinstanz-Vorrang für alle Schlüsseloperationen</h3>
        <p>
          Jede Redis-Operation, deren Korrektheit davon abhängt, was gerade geschrieben wurde
          zurückzulesen, muss die Schreibinstanz verwenden. Lesereplikate sind nur für
          leseintensive, nicht kritische Abfragen.
        </p>
      </div>
      <p>
        Selbstaudits auf diesem Spezifitätsniveau sind ein ständiges Engagement. Wir werden
        weiterhin veröffentlichen, was wir finden. Zukünftige Berichte werden auf dem
        <a href="<?php echo transparency_href('/transparency/'); ?>">Transparenz-Hub</a> veröffentlicht.
      </p>
    </section>

    <section class="doc-section">
      <h2>Offenlegungszeitplan</h2>
      <table class="doc-table" aria-label="Offenlegungszeitplan">
        <thead>
          <tr>
            <th scope="col">Datum</th>
            <th scope="col">Ereignis</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">12. Mai 2026</time></td>
            <td>Befunde während einer routinemäßigen internen Audit-Sitzung identifiziert</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12. Mai 2026</time></td>
            <td>Alle Korrekturen implementiert und committed (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12. Mai 2026</time></td>
            <td>Vollständige PHPUnit-Regressionssuite bestanden, PHPStan Level 9 sauber</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12. Mai 2026</time></td>
            <td>Zu origin/main gepusht</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12. Mai 2026</time></td>
            <td>Dieser Transparenzartikel veröffentlicht</td>
          </tr>
        </tbody>
      </table>
      <p>
        Alle Befunde wurden intern identifiziert. Kein externer Bericht, CVE oder Verstoß ging
        dieser Offenlegung voraus. Es gibt keine Hinweise, dass einer der Befunde ausgenutzt wurde.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
