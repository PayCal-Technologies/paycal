<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'SOC 2 Compliance bei PayCal - [PayCal]';
$pageLabel = 'SOC 2 Compliance bei PayCal';

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Transparenz-Hub</a>
    <span class="separator">/</span>
    <span class="current">SOC 2 Compliance bei PayCal</span>
  </nav>

  <header class="doc-article-header">
    <h1>PayCal SOC 2 Bereitschaft &amp; Sicherheitsmodell</h1>
    <p class="deck">Ein technischer Überblick, wie PayCal SOC 2-Kontrollen auf durchgesetzte Systemverhaltensweisen und kontinuierlich generierte Nachweise abbildet.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Übersicht</h2>
      <p>PayCal betreibt ein SOC 2-konformes Sicherheitsprogramm, das auf verifizierbarer Durchsetzung und nachverfolgbaren Nachweisen basiert – nicht auf reinen Richtlinienaussagen.</p>
      <ul class="doc-fact-list">
        <li><strong>Kontrollen im Geltungsbereich:</strong> CC1-CC9</li>
        <li><strong>Artefakte im aktuellen Bundle:</strong> 37</li>
        <li><strong>Kontroll-zu-Artefakt-Mappings:</strong> 26</li>
        <li><strong>Nachweis-Aktualitätsfenster:</strong> 35 Tage</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Kontrollabdeckung (CC1-CC9)</h2>
      <p>Alle SOC 2 Common Criteria-Kontrollen im Geltungsbereich (CC1 bis CC9) sind den im monatlichen Bundle enthaltenen Nachweisen zugeordnet.</p>
      <p>Dieses Mapping unterstützt die direkte Rückverfolgbarkeit vom Kontrollziel zu konkreten Artefakten, die für Überprüfungen verwendet werden.</p>
    </section>

    <section class="doc-section">
      <h2>3. Wie Kontrollen durchgesetzt werden</h2>
      <p>PayCal behandelt die Durchsetzung als Systemeigenschaft. Kontrollen werden programmatisch durchgesetzt, nicht nur dokumentiert.</p>
      <ul class="doc-fact-list">
        <li><strong>Authentifizierung:</strong> Passkey-fähiger Authentifizierungsfluss zur Stärkung der Phishing-Resistenz.</li>
        <li><strong>Laufzeitintegrität:</strong> Laufzeit-Integritätsüberwachung mit Handhabung des Betriebszustands.</li>
        <li><strong>Ausgabehärtung:</strong> Guardian-Bereinigungskontrollen für sensible DOM-/Ausgabepfade.</li>
        <li><strong>Qualitätsgatter:</strong> Automatisiertes vollständiges PHPUnit-Gatter, bevor Bundle-Nachweise akzeptiert werden.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Änderungsmanagement &amp; Tests</h2>
      <p>Die Änderungs-Governance ist auf CC8 ausgerichtet mit verfolgten Änderungen, Genehmigungen und Testnachweisen.</p>
      <ul class="doc-fact-list">
        <li><strong>Änderungseinträge:</strong> 12</li>
        <li><strong>Genehmigungseinträge:</strong> 10</li>
        <li><strong>Testergebnisse:</strong> 1528 Tests, 8351 Assertions (bestanden)</li>
        <li><strong>Test-Kontroll-Nachweis:</strong> 5 Suites, 5 bestanden, 8 verlinkte Testdateien</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Audit-Trail &amp; Nachweisintegrität</h2>
      <p>Administrative und sicherheitsrelevante Laufzeitereignisse werden mit unveränderlicher Ledger-Validierung für Integritätsprüfungen exportiert.</p>
      <p><strong>Aktueller Ledger-Integritätsstatus:</strong> BESTANDEN.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Kontinuierliche Überwachung &amp; Aktualität</h2>
      <p>Nachweis-Exporte laufen kontinuierlich und werden gegen eine deterministische Aktualitätsrichtlinie validiert.</p>
      <p><strong>Aktuelles Aktualitätsergebnis:</strong> alle gemappten Artefakte befinden sich im 35-tägigen Audit-Fenster.</p>
    </section>

    <section class="doc-section">
      <h2>7. Aktueller Status</h2>
      <p><strong>Status:</strong> SOC 2 Bereitschaft in Bearbeitung, mit kontinuierlicher Kontrollhärtung und deterministischen Nachweisahtualisierungen.</p>
      <p>PayCal beansprucht keine SOC 2 Zertifizierung oder Prüferurteil auf dieser Seite. Der Zugang zum formellen Bericht bleibt NDA-gesichert.</p>
    </section>

    <section class="doc-section">
      <h2>Wiederverwendbare Compliance-Snippets</h2>
      <p><strong>Footer-Badge:</strong> Vorbereitung läuft • Kontrollen Gemappt • Kontinuierliche Nachweisüberwachung</p>
      <p><strong>Zusammenfassungsblock:</strong> CC1-CC9 gemappt, 37 Artefakte, 26 Kontroll-Links, Ledger-Integrität bestanden und automatisierter vollständiger Testnachweis.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Referenzen</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Bereinigierte öffentliche Kontrollzusammenfassung, deterministische Narrative und Sicherheitskontaktpfad.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">PayCal SOC 2 Zusammenfassung</a>
          <span class="doc-ref-desc">Status, Metriken und NDA-Zugang für diesen Bericht.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">SOC 2 Bericht anfordern (NDA)</a>
          <span class="doc-ref-desc">Gesicherter Zugang für Anbieter- und Sicherheits-Due-Diligence-Überprüfungen.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Offizieller Standard</a>
          <span class="doc-ref-desc">Der maßgebliche Rahmen, der die SOC 2-Kriterien definiert.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Übersicht über Geschichte und Umfang der System- und Organisationskontrollen.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Reddit-Community</a>
          <span class="doc-ref-desc">Praktikerdiskussion zu SOC 2-Audits und Vorbereitung.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
