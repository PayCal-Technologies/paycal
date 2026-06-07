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
$pageTitle = 'Foutafhandeling en berichtnormalisatie - [PayCal]';
$pageLabel = 'Foutafhandeling en berichtnormalisatie';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Foutafhandeling en berichtnormalisatie</span>
  </nav>

  <header class="doc-article-header">
    <h1>Foutafhandeling en berichtnormalisatie</h1>
    <p class="deck">
      Hoe PayCal foutrapportage standaardiseert voor alle frontend-modules om te garanderen
      dat gebruikers zinvolle, veilige en consistente foutmeldingen ontvangen zonder gevoelige details bloot te stellen.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Overzicht en doel</h2>
      <p>
        Wanneer gebruikers fouten tegenkomen (netwerkstoringen, toegang geweigerd, validatiefouten),
        verdienen zij duidelijke feedback die uitlegt wat er is misgegaan en hoe dit op te lossen. Echter
        moeten ruwe foutmeldingen vanuit de backend worden genormaliseerd om:
      </p>
      <ul class="doc-list">
        <li><strong>Ruis te verwijderen:</strong> Verwijder overbodige &quot;Fout:&quot;-voorvoegsels en onnodige witruimte</li>
        <li><strong>Lekkage te voorkomen:</strong> Zorg ervoor dat gevoelige implementatiedetails de gebruiker nooit bereiken</li>
        <li><strong>Terugvalopties te bieden:</strong> Toon veilige berichten wanneer fouten leeg of misvormd zijn</li>
        <li><strong>Consistentie te waarborgen:</strong> Pas dezelfde logica toe op alle 11+ frontend-modules</li>
        <li><strong>Foutopsporing te verbeteren:</strong> Registreer volledige foutdetails in Phantom Wing en toon veilige samenvattingen aan gebruikers</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Het probleem: generieke vs. zinvolle fouten</h2>
      <p>
        Vóór standaardisatie gebruikten PayCal-modules ad-hoc foutafhandeling:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ SLECHT: Toont ruwe fout, dupliceert logica
PC.showToast(error?.message || 'Import mislukt.');
PW.error(`Import mislukt: ${error.message}`);</code></pre>
      </div>
      <p>Problemen met deze aanpak:</p>
      <ul class="doc-list">
        <li>Gebruikers zien verwarrende ruwe berichten zoals &quot;ECONNREFUSED: Verbinding geweigerd&quot;</li>
        <li>Elke module implementeert zijn eigen terugvallogica onafhankelijk</li>
        <li>Geen consistente witruimte-opruiming of verwijdering van voorvoegsels</li>
        <li>Lege foutberichten kunnen in de UI als &quot;undefined&quot; worden weergegeven</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>De oplossing: gestandaardiseerde foutresolver</h2>
      <p>
        Alle PayCal frontend-modules gebruiken nu een uniforme resolver-functie die foutberichten normaliseert:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ GOED: Genormaliseerd, consistent, veilig
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Haal het bericht op uit het foutobject
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // Verwijder het &quot;Error:&quot;-voorvoegsel en verwijder witruimte
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Retourneer genormaliseerd indien niet leeg; anders veilig terugval
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Gebruik:</strong></p>
      <div class="doc-code-block">
        <pre><code>// In catch-blokken over alle modules
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'Profiel bijwerken mislukt.');
  PC.showToast(message, 'error');  // Gebruiker ziet zinvolle feedback
  PW.error(message);                // Geregistreerd voor foutopsporing
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Implementatiebereik</h2>
      <p>
        Vanaf april 2026 is dit gestandaardiseerde foutafhandelingspatroon toegepast op
        <strong>11 frontend-modules</strong> met <strong>~40+ genormaliseerde catch-blokken</strong>:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Authenticatie &amp; instellingen (7 modules)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Kern- &amp; datamodules (4 modules)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>Modules met hoge waarde (10+ catch-punten):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/organizations/index.php</code> — Organisatiebeheer, toegangsverzoeken, auditsporen (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — Site-CRUD, verdiensten, herstel van zwevend werk (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Dagboekinvoerbewerkingen, kopiëren/plakken/verwijderen (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Foutcategorieën &amp; afhandelingspatronen</h2>
      <p>De resolver wordt consistent toegepast over meerdere foutcategorieën:</p>
      
      <h3>1. Netwerkaanvraagfouten</h3>
      <div class="doc-code-block">
        <pre><code>// Netwerkmodule: HTTP-fouten, time-outs, verbindingsproblemen
async function deleteResource(ep, id) {
  try {
    // ...fetch-logica...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Netwerkfout');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. Afhandeling van API-reacties</h3>
      <div class="doc-code-block">
        <pre><code>// Facturering/Instellingen: Server stuurde foutbericht in payload terug
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'Factureringsstatus kan niet worden geladen.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'Factureringsstatus kan niet worden geladen.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. Fouten bij UI-bewerkingen</h3>
      <div class="doc-code-block">
        <pre><code>// Kalender/Organisaties: door gebruiker geïnitieerde acties (plakken, verwijderen, bijwerken)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('Geslaagd!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'Actie mislukt. Probeer opnieuw.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Asynchroon initialiseren</h3>
      <div class="doc-code-block">
        <pre><code>// Kernmodules: Opstartfouten of afhankelijke initialisatiefouten
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'Navigatie-initialisatie mislukt');
  PW.warn(resolved);  // Geregistreerd maar blokkeert pagina niet
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Beveiligingsoverwegingen</h2>
      <p>
        Normalisatie van foutberichten beschermt de privacy van gebruikers en de systeemintegriteit:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Geen databasedetails:</strong> Backend-fouten zoals &quot;UNIQUE constraint failed on email&quot;
          worden onderschept aan de API-grens en vervangen door gebruiksvriendelijke berichten
        </li>
        <li>
          <strong>Geen bestandspaden:</strong> Systeemfouten die bestandspaden of procesdetails blootleggen worden verwijderd
        </li>
        <li>
          <strong>Geen authenticatielekkage:</strong> Reacties op authenticatiefouten onthullen nooit
          of een account bestaat (alleen tijdveilige generieke berichten)
        </li>
        <li>
          <strong>Geen CORS/netwerkdetails:</strong> Fouten op transportniveau worden genormaliseerd naar
          generieke &quot;Verbindingsfout&quot;-berichten
        </li>
        <li>
          <strong>Veilige terugvalopties:</strong> Alle catch-blokken hebben expliciete terugvalberichten;
          toont nooit &quot;undefined&quot; of &quot;null&quot;
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Voordelen voor de gebruikerservaring</h2>
      <p>
        Gestandaardiseerde foutberichten verbeteren de gebruikerservaring aanzienlijk:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Duidelijke feedback:</strong> Gebruikers weten wat er is mislukt (bijv. &quot;Toegangssleutel niet herkend&quot;
          vs. het generieke &quot;Aanmelden mislukt&quot;)
        </li>
        <li>
          <strong>Uitvoerbare vervolgstappen:</strong> Waar mogelijk suggereren berichten oplossingen
          (&quot;Opnieuw proberen&quot;, &quot;Controleer uw verbinding&quot;, &quot;Neem contact op met de ondersteuning&quot;)
        </li>
        <li>
          <strong>Consistentie door de app:</strong> Dezelfde soorten fouten worden overal op dezelfde manier weergegeven,
          wat verwarring bij gebruikers vermindert
        </li>
        <li>
          <strong>Toegankelijke foutstatussen:</strong> Schermlezers kondigen genormaliseerde berichten aan;
          logboekregistratie biedt volledige context voor ondersteuningsteams
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Debuggen &amp; ondersteuningsworkflow</h2>
      <p>
        Fout­normalisatie offert <strong>geen</strong> debuggingcapaciteit op. Volledige foutdetails
        vloeien naar Phantom Wing:
      </p>
      <div class="doc-code-block">
        <pre><code>// Gebruiker ziet schoon UI-bericht
PC.showToast(resolveThrownMessage(error, 'Upload mislukt.'), 'error');

// Ondersteuningsteam ziet volledige details in Phantom Wing-logboeken
PW.error('Upload mislukt', {
  userMessage: resolveThrownMessage(error, 'Upload mislukt.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Testen &amp; kwaliteitsborging</h2>
      <p>
        Alle wijzigingen in foutafhandeling worden gevalideerd vóór inzet:
      </p>
      <ul class="doc-list">
        <li><strong>Syntaxisvalidatie:</strong> <code>php -l</code> en <code>node --check</code> verifiëren de juistheid</li>
        <li><strong>Typebeveiliging:</strong> Editorthermometer bevestigt geen typeregressies</li>
        <li><strong>Integratietests:</strong> Catch-blokken getest met nep-foutobjecten</li>
        <li><strong>Phantom Wing-logboekregistratie:</strong> Foutberichten geverifieerd in debuglogboeken</li>
        <li><strong>Toegankelijkheidsaudit:</strong> Aankondigingen van schermlezers getest op duidelijkheid</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Onderhoud &amp; toekomstige uitbreidingen</h2>
      <p>
        Dit patroon is ontworpen voor onderhoudbaarheid op de lange termijn:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Gereed voor lokalisatie:</strong> Foutberichten kunnen via i18n worden doorgesluisd
          zonder de resolver-logica te hoeven aanpassen
        </li>
        <li>
          <strong>Uitbreidbaar:</strong> De resolver kan worden uitgebreid om foutcodes, herhaalpogingen
          of gespecialiseerde berichtopzoeking te verwerken zonder bestaande code te breken
        </li>
        <li>
          <strong>Documentatie:</strong> Elke module bevat inline commentaar dat
          foutscenario's en terugvalstrategieën uitlegt
        </li>
        <li>
          <strong>Git-geschiedenis:</strong> Alle wijzigingen bijgehouden met gedetailleerde commitberichten en
          diffs op bestandsniveau voor eenvoudige beoordeling
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Samenvatting: de PayCal-standaard voor foutafhandeling</h2>
      <p>
        PayCal's gestandaardiseerde normalisatie van foutberichten garandeert dat:
      </p>
      <ol class="doc-list">
        <li>Gebruikers duidelijke, bruikbare foutfeedback ontvangen</li>
        <li>Gevoelige systeemdetails nooit naar de frontend lekken</li>
        <li>Berichtafhandeling consistent is over alle 11+ frontend-modules</li>
        <li>Foutopsporings- en ondersteuningsteams volledige foutcontext behouden via Phantom Wing</li>
        <li>Code onderhoudbaar, testbaar en toegankelijk is</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Deze toewijding aan beveiliging, duidelijkheid en consistentie weerspiegelt PayCal's toewijding
        aan gebruikersvertrouwen en transparante informatiedeling.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
