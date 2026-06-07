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
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Opt-in Diagnostiek & Phantom Wing - [PayCal]';
$pageLabel = 'Opt-in Diagnostiek & Phantom Wing';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Opt-in Diagnostiek &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1>Opt-in Diagnostiek &amp; Phantom Wing</h1>
    <p class="deck">
      PayCal bevat een optionele diagnostieklaag die u beheert. Hier staat precies wat deze
      verzamelt, wat op uw apparaat blijft en hoe het wordt gebruikt.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Overzicht</h2>
      <p>
        PayCal wordt geleverd met een ingebouwde diagnostieklaag genaamd <strong>Phantom Wing</strong>.
        Standaard is deze bijna volledig stil — het registreert alleen ernstige, onverwerkte fouten
        en verzendt nooit iets zonder uw expliciete opt-in.
      </p>
      <p>
        Als u een probleem ondervindt en meer context met ondersteuning wilt delen, kunt u extra
        diagnostiek inschakelen via
        <a href="/settings/">Instellingen → Foutopsporing (Optioneel)</a>.
        Elke instelling is onafhankelijk; u kunt alleen de relevante inschakelen.
        Alle drie staan standaard op <strong>Uit</strong>.
      </p>
    </section>

    <section class="doc-section">
      <h2>De drie opt-in-besturingen</h2>
      <p>
        Elk besturingselement bevindt zich in het paneel <strong>Foutopsporing (Optioneel)</strong>
        onderaan uw Instellingen-pagina. Ze zijn alleen bedoeld voor probleemoplossing — het
        inschakelen kan pagina-interacties iets vertragen omdat er extra werk in de browser wordt
        verricht.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Instelling</th>
            <th>Wat het activeert</th>
            <th>Wie het ziet</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Consoleberichten</strong></td>
            <td>
              Zendt waarschuwingen, informatielogboeken en prestatiemarkeringen uit naar de
              ontwikkelaarsconsole van uw browser. Handig voor zelfdiagnose — open DevTools en
              zoek naar berichten met het voorvoegsel <code>[PayCal]</code> of emoji-markeringen.
            </td>
            <td>Alleen u (uw browserconsole, nooit verzonden)</td>
          </tr>
          <tr>
            <td><strong>Gedetailleerde diagnostiek</strong></td>
            <td>
              Schakelt stapsgewijze interne gebeurtenisregistratie in. Phantom Wing legt de
              volledige levenscyclus van bewerkingen (kalenderladingen, formulierinzendingen,
              sessiegebeurtenissen) vast in een in-memory logboek dat is opgenomen in elk
              ondersteuningsrapport dat u kiest te delen.
            </td>
            <td>Alleen u, tenzij u een ondersteuningsrapport deelt</td>
          </tr>
          <tr>
            <td><strong>Netwerkinzichten</strong></td>
            <td>
              Registreert API-verzoektijden — hoe lang elke server-retourvlucht duurt,
              responsgroottes en of batchverwerking of caching is toegepast. Helpt bij het
              diagnosticeren van traagheid bij specifieke bewerkingen.
            </td>
            <td>Alleen u (uw browserconsole, nooit verzonden)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Wat Phantom Wing standaard doet</h2>
      <p>
        Zelfs als alle drie besturingen uitstaan, voert Phantom Wing een lichtgewicht
        basismonitor uit die alleen ernstige storingen registreert:
      </p>
      <ul class="doc-list">
        <li>Niet-gevangen JavaScript-uitzonderingen (<code>window.onerror</code>)</li>
        <li>Onverwerkte promise-afwijzingen</li>
        <li>Fetch-aanroepen die mislukken met een netwerkfout (geen HTTP-fouten — die worden per functie afgehandeld)</li>
      </ul>
      <p>
        Deze basisgegevens blijven volledig in het geheugen en worden nooit ergens naartoe
        verzonden. Ze worden weergegeven in een samenvatting van één seconde in de browserconsole
        bij het laden van de pagina, zodat u snel kunt zien of er iets mis is gegaan, en worden
        daarna verwijderd.
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
        Phantom Wing heeft een lichtgewicht telemetriekanaal dat wordt gebruikt om de
        functiebetrouwbaarheid geaggregeerd te meten — bijvoorbeeld om te detecteren of een
        bepaalde bewerking met een ongebruikelijk hoge frequentie mislukt op het platform.
      </p>
      <h3>Wat telemetrie verzendt</h3>
      <ul class="doc-list">
        <li>Geanonimiseerde gebeurtenistellingen per uur (bijv. <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Foutcategorie en -type — nooit het volledige foutbericht of de stack-trace</li>
        <li>Geen gebruikersidentificatoren, geen sessietokens, geen IP-adressen</li>
      </ul>
      <h3>Wat telemetrie nooit verzendt</h3>
      <ul class="doc-list">
        <li>Uw naam, e-mail of accountgegevens</li>
        <li>Inkomsten, loonperiode of financiële gegevens</li>
        <li>Volledige foutberichten of stack-traces</li>
        <li>URL-paden of querystrings</li>
        <li>Toetsaanslagen of formulierveldwaarden</li>
      </ul>
      <h3>Frequentielimiet &amp; terugval</h3>
      <p>
        Telemetrie-inzendingen zijn server-side beperkt per gebruiker per minuut. Als uw client
        de drempel overschrijdt, bevestigt de server dit stilzwijgend en verwijdert het overschot
        — er wordt niets opgeslagen. De client past ook exponentiële terugval toe: na twee
        opeenvolgende server-side fouten schakelt het automatisch telemetrie-inzending tien
        minuten uit.
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
      <h2>Gegevensredactie</h2>
      <p>
        Voordat een waarde in het geheugen wordt opgeslagen of via telemetrie wordt verzonden,
        past Phantom Wing een automatische redactiestap toe. Waarden die overeenkomen met bekende
        gevoelige patronen worden vervangen door <code>[REDACTED]</code>:
      </p>
      <ul class="doc-list">
        <li>E-mailadressen</li>
        <li>Bearer-tokens en autorisatieheaderwaarden</li>
        <li>CSRF-tokens</li>
        <li>Tekenreeksen die eruitzien als cryptografische sleutels of base64-gecodeerde blobs boven een minimumlengte</li>
      </ul>
      <p>
        Redactie werkt op alle argumenten die worden doorgegeven aan onderschepte consolemethoden
        en alle telemetrie-veldwaarden vóór de wachtrij. Het kan niet worden omzeild door
        diagnostische instellingen in te schakelen.
      </p>
    </section>

    <section class="doc-section">
      <h2>Bereikbewakers: pagina's waar diagnostiek wordt onderdrukt</h2>
      <p>
        Telemetrie-inzending wordt volledig onderdrukt op authenticatiepagina's (<code>/auth/</code>).
        Dit betekent dat zelfs als Netwerkinzichten is ingeschakeld, er geen telemetrie wordt
        verzonden terwijl u zich in de aanmeld-, registratie- of herstelstromen bevindt. Dit is
        een verdediging-in-diepte-maatregel om te voorkomen dat gegevens die grenzen aan
        inloggegevens in diagnostische kanalen verschijnen.
      </p>
    </section>

    <section class="doc-section">
      <h2>Uw beheer</h2>
      <p>
        Alle drie diagnostische instellingen worden opgeslagen als accountvoorkeuren, niet als
        browsercookies. Ze volgen uw account op alle apparaten en sessies en staan standaard
        op <strong>Uit</strong> voor elk account — inclusief nieuwe accounts.
        U kunt ze op elk moment wijzigen in
        <a href="/settings/">Instellingen → Foutopsporing (Optioneel)</a>.
      </p>
      <p>
        Het uitschakelen van een instelling wordt onmiddellijk van kracht bij het volgende laden
        van de pagina. Er worden geen diagnostische gegevens bewaard tussen sessies: het
        in-memory logboek van Phantom Wing wordt gewist wanneer u ergens anders naartoe navigeert
        of het tabblad sluit.
      </p>
    </section>

    <section class="doc-section">
      <h2>Samenvatting</h2>
      <ol class="doc-list">
        <li>Alle drie foutopsporingsbesturingen staan standaard <strong>Uit</strong> en moeten expliciet door u worden ingeschakeld</li>
        <li>Consoleberichten en Netwerkinzichten verlaten nooit uw apparaat</li>
        <li>Gedetailleerde diagnostiek blijft in het geheugen en wordt alleen gedeeld als u een ondersteuningsrapport deelt</li>
        <li>Telemetrie verzendt alleen geanonimiseerde, geaggregeerde gebeurtenistellingen — nul persoonlijke gegevens</li>
        <li>Alle waarden worden geredigeerd vóór opslag of verzending, ongeacht diagnostische instellingen</li>
        <li>Telemetrie wordt volledig onderdrukt op alle authenticatiepagina's</li>
        <li>Frequentielimieten en automatische clientterugval voorkomen per ongeluk te veel rapporteren</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Phantom Wing is zo ontworpen dat u alle diagnostiek voor onbepaalde tijd uitgeschakeld
        kunt laten. De opt-in-besturingen bestaan om u en het ondersteuningsteam een gedeelde
        taal te geven wanneer er iets misgaat — niet om standaard gegevens te verzamelen.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
