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
$pageTitle = 'Auth-, Passkey- en Redis-hardening — Mei 2026 - [PayCal]';
$pageLabel = 'Auth-, Passkey- & Redis-hardening — Mei 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Auth-, Passkey- &amp; Redis-hardening — Mei 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Auth-, Passkey- &amp; Redis-hardening — Mei 2026</h1>
    <p class="deck">
      Op 12 mei 2026 hebben we een interne audit uitgevoerd van onze authenticatie-, passkey- en
      Redis-infrastructuur. We vonden elf problemen — allemaal in code die we zelf hadden geschreven.
      Dit artikel documenteert wat we vonden, waarom het belangrijk was en wat we precies hebben gewijzigd.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Samenvatting</h2>
      <table class="doc-table" aria-label="Samenvatting van de auditbevindingen">
        <tbody>
          <tr>
            <td><strong>Auditdatum</strong></td>
            <td>12 mei 2026</td>
          </tr>
          <tr>
            <td><strong>Reikwijdte</strong></td>
            <td>Authenticatie, passkey (WebAuthn) en Redis-infrastructuur</td>
          </tr>
          <tr>
            <td><strong>Totale bevindingen</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Verdeling naar ernst</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Herstatus</strong></td>
            <td>Alle bevindingen opgelost in commit <code>493d5e44</code>. Volledige testsuite geslaagd. Geen regressies.</td>
          </tr>
          <tr>
            <td><strong>Bewijs van misbruik</strong></td>
            <td>Geen</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Waarom we dit publiceren</h2>
      <p>
        We vonden deze problemen in onze eigen applicatiecode en infrastructuurlagen — niet in
        afhankelijkheden van derden of externe diensten. Code die we hebben beoordeeld, gecommit
        en geleverd.
      </p>
      <p>
        We publiceren dit omdat veiligheidstransparantie meer vereist dan het openbaar maken van
        externe CVE's of het slagen voor audits. Het betekent publiekelijk verantwoording afleggen
        wanneer ons eigen team code levert die niet voldoet aan de standaard die we onszelf hebben
        gesteld.
      </p>
      <p>
        We schamen ons hier niet voor. De ergere mislukking zou zijn geweest om deze problemen te
        ontdekken en te kiezen ze niet openbaar te maken.
      </p>
    </section>

    <section class="doc-section">
      <h2>Auditmethodologie</h2>
      <p>
        Deze audit werd intern uitgevoerd door het engineeringteam op 12 mei 2026. De beoordeling
        omvatte alle codepaden die verband houden met authenticatiestatesbeheer, de levenscyclus van
        WebAuthn-referenties en Redis-sleutelbeheer.
      </p>
      <ul class="doc-list">
        <li><strong>Handmatige codebeoordeling</strong> van alle controller-, domein- en infrastructuurbestanden betrokken bij sessiecreatie, passkey-registratie, passkey-login en accountherstelstromen.</li>
        <li><strong>Statische analyse</strong> via PHPStan Niveau 9 — nultolerantie voor type-onveilige of onbereikbare codepaden.</li>
        <li><strong>Bedreigingsmodellering</strong> tegen de WebAuthn Niveau 2-specificatie (§6.1 authenticatorgegevens, §7.1 registratieceremonie, §7.2 authenticatieceremonie).</li>
        <li><strong>Regressietesten</strong> met de volledige PHPUnit-regressiesuite na herstel. Alle tests geslaagd.</li>
      </ul>
      <p>Geen externe auditor, bug bounty-rapport of beveiligingsincident ging vooraf aan deze beoordeling. Deze problemen werden geïdentificeerd via een routinematig intern proces.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Onze engineeringfilosofie</h2>
      <p>Deze audit onthulde tekortkomingen in drie principes die we als fundamenteel beschouwen:</p>
      <ul class="doc-list">
        <li>
          <strong>Atomiciteit vóór correctheid.</strong> Als twee bewerkingen samen moeten plaatsvinden,
          behandel ze dan als één bewerking of probeer het ontwerp helemaal niet. Een systeem dat
          &ldquo;de meeste tijd correct&rdquo; is, is niet correct.
        </li>
        <li>
          <strong>Gelaagde verdediging.</strong> Geen enkele controle mag de enige barrière zijn bij
          een beveiligingsgrens. Als de database een referentie als ingetrokken markeert, moet het
          registratiepad dit ook afdwingen. De verdediging mag geen gaten hebben tussen lagen.
        </li>
        <li>
          <strong>Informatieasymmetrie als ontwerpdoel.</strong> Een aanvaller die het systeem sondert,
          moet zo min mogelijk leren over wat er van binnen gebeurt. Foutmeldingen, logvermeldingen en
          responstijden zijn allemaal blootstellingsoppervlakken.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Bevinding 1 &mdash; Niet-atomaire <code>hset + expire</code> (Redis Race Condition) <span class="doc-badge high">High</span></h2>
      <p><strong>Categorie: Redis / Atomiciteit</strong></p>
      <p>
        Op negen aanroepplaatsen werd een Redis-hash geschreven met <code>HSET</code> en vervolgens
        onmiddellijk een TTL toegewezen met een afzonderlijke <code>EXPIRE</code>-opdracht:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        Dat zijn twee afzonderlijke round trips naar Redis. Als het PHP-proces afsluit, wordt
        onderbroken, een time-out bereikt of Redis een momentane storing ervaart tussen de twee,
        wordt de hash zonder vervaltijd geschreven — en leeft hij voor altijd in Redis.
      </p>
      <p>De getroffen aanroepplaatsen en hun beveiligingsimplicaties:</p>
      <table class="doc-table" aria-label="Getroffen aanroepplaatsen voor niet-atomaire hset+expire">
        <thead>
          <tr>
            <th scope="col">Aanroepplaats</th>
            <th scope="col">Sleuteltype</th>
            <th scope="col">Gevolg van ontbrekende TTL</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Sessierecord</td>
            <td>Sessie verloopt nooit — account toegankelijk buiten de beoogde levensduur</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (enrollment challenge)</td>
            <td>WebAuthn-challenge</td>
            <td>Verouderde challengegegevens blijven voortbestaan buiten de beoogde levensduur, waardoor het herhalingsrisico toeneemt</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (register challenge)</td>
            <td>WebAuthn-challenge</td>
            <td>Zelfde als hierboven</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (login challenge)</td>
            <td>WebAuthn-challenge</td>
            <td>Zelfde als hierboven</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Herstel-passkeychallenge</td>
            <td>Herstelssessiegegevens verlopen nooit</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (code-uitgifte)</td>
            <td>Herstel-e-mailcode</td>
            <td>Eenmalige codes overleven hun beoogde vervaltijdvenster</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (code opnieuw verzenden)</td>
            <td>Herstel-e-mailcode</td>
            <td>Zelfde als hierboven</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Eenmalige beheerderstokens</td>
            <td>Tokens die in 5 minuten moeten verlopen, kunnen voor onbepaalde tijd voortbestaan</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Herstelstransactierecord</td>
            <td>Herstelstransactiestatus wordt nooit opgeschoond</td>
          </tr>
        </tbody>
      </table>
      <p>
        Voor sessies is dit een directe schending van de toegangslevensduur. Een sessie moet een
        harde bovengrens hebben. Als de TTL nooit wordt ingesteld, bestaat die bovengrens niet.
      </p>
      <p>
        Voor eenmalige capaciteitstokens kan een token die ontworpen is om precies 300 seconden
        geldig te zijn, dagen later nog steeds geldig zijn.
      </p>
      <p><strong>De oplossing:</strong> We hebben <code>Database::hsetex()</code> geïntroduceerd — een wrapper die
      beide bewerkingen uitvoert binnen een Redis <code>MULTI/EXEC</code>-transactie, waardoor ze atomair worden.
      De bewerkingen worden uitgevoerd in dezelfde uitvoeringseenheid, zodat de sleutel niet kan bestaan zonder
      dat zijn TTL wordt toegepast. De sleutel heeft ofwel data en een TTL, ofwel niets.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Elke aanroepplaats die een <code>hset</code> gevolgd door <code>expire</code> op dezelfde sleutel uitgaf, is geconverteerd.</p>
    </section>

    <section class="doc-section">
      <h2>Bevinding 2 &mdash; Uitloggen en CSRF-invalidering konden stil falen <span class="doc-badge high">High</span></h2>
      <p><strong>Categorie: Redis / Uitloggen, CSRF</strong></p>
      <p>
        De methode <code>Database::del()</code> — verantwoordelijk voor het verwijderen van
        Redis-sleutels op patroon — enumereerde sleutels via de <em>leesreplica</em> en stuurde
        vervolgens <code>DEL</code>-opdrachten naar het <em>primaire</em>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        Redis-replicatie is asynchroon. Als de replica achterloopt — zelfs met milliseconden —
        bevat deze mogelijk de sleutel die zojuist is geschreven nog niet. In dat geval retourneert
        <code>keys()</code> een lege lijst en wordt er geen <code>DEL</code> naar het primaire
        gestuurd. De sleutel overleeft.
      </p>
      <p>De twee meest kritieke aanroepers van <code>del()</code>:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — uitloggen:</strong> Wanneer een gebruiker uitlogt,
          verwijderen we zijn sessiesleutel. Als de replica achterloopt, retourneert de lijst van
          sessiesleutels leeg, wordt de verwijdering nooit geactiveerd en bestaat de sessie nog
          steeds op het primaire. De gebruiker gelooft uitgelogd te zijn. Dat is niet zo.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — nonce-invalidering:</strong> CSRF-tokens zijn
          eenmalige nonces. Na eerste gebruik moeten ze worden verwijderd. Als de verwijdering nooit
          wordt geactiveerd, kan het token worden hergebruikt in een tweede verzoek. Eenmalig wordt herbruikbaar.
        </li>
      </ul>
      <p>
        Deze bug is subtiel omdat hij zich alleen manifesteert onder belasting of tijdens tijdelijke
        replicavertraging. Bij ontwikkeling tegen een enkele Redis-instantie wordt hij nooit geactiveerd.
      </p>
      <p><strong>De oplossing:</strong> Sleutelenumeratie en -verwijdering moeten dezelfde instantie als doel hebben.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bevinding 3 &mdash; WebAuthn-gebruikersverificatie-bypass <span class="doc-badge high">High</span></h2>
      <p><strong>Categorie: Authenticatie</strong></p>
      <p>
        In <code>AccountRecoveryController</code>, bij het registreren van een passkey als onderdeel
        van accountherstel, werd bij de aanroep van <code>processCreate()</code> <code>false</code>
        doorgegeven voor <code>requireUserVerification</code>:
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
        De challenge die naar de client was gestuurd, specificeerde <code>userVerification: 'required'</code>
        — aan de authenticator werd verteld dat de gebruiker een biometrische verificatie of PIN moest
        voltooien. Maar bij het verifiëren van het antwoord vertelden we de bibliotheek dat de UV-vlag
        niet hoefde te worden gehandhaafd.
      </p>
      <p>
        Een gewijzigde client kon een authenticatorantwoord indienen met de UV-bit gewist. Onze server
        zou dit accepteren zonder te eisen dat de biometrische verificatie daadwerkelijk had plaatsgevonden.
      </p>
      <p>
        De accountherstelstroom is het pad dat een gebruiker inslaat wanneer hij de toegang tot zijn
        andere referenties heeft verloren. Dit is het hoogste-risico authenticatieoppervlak dat we
        beheren. Het verzwakken van biometrische handhaving hier is precies de verkeerde afweging.
      </p>
      <p><strong>De oplossing:</strong> UV wordt nu gehandhaafd. Een antwoord waarbij de authenticatorgegevens de UV-vlag niet dragen, wordt geweigerd.</p>
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
      <h2>Bevinding 4 &mdash; Kloondetectie via handtekeningteller miste herhalingsaanvallen <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categorie: Authenticatie</strong></p>
      <p>Onze passkey-kloondetectie controleerde:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        De WebAuthn Niveau 2-specificatie (§6.1) stelt: als de opgeslagen handtekeningteller niet nul
        is en de nieuwe handtekeningteller niet <em>strikt groter</em> is dan de opgeslagen waarde,
        moet de referentie als mogelijk gekloneerd worden beschouwd. Onze conditie vereiste <code>&lt;</code>,
        niet <code>&lt;=</code>, dus een gelijke teller — zoals bij een herhalingsaanval — passeerde
        zonder de kloonvlag te activeren.
      </p>
      <p><strong>De oplossing:</strong> Afgestemd op de specificatie.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bevinding 5 &mdash; Handtekeningteller werd niet altijd bewaard <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categorie: Authenticatie</strong></p>
      <p>Na een succesvolle passkey-login was de update van de handtekeningteller afhankelijk van of deze niet nul was:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Sommige authenticators retourneren <code>0</code> als schildwacht wat betekent &ldquo;dit
        apparaat implementeert geen teller.&rdquo; Als een apparaat later een echte teller begint te
        retourneren (firmware-update, of de gebruiker registreert dezelfde referentie op een platform
        dat tellers ondersteunt), zouden we de eerste echte teller nooit bewaren omdat we <code>0</code>
        voor altijd hadden opgeslagen.
      </p>
      <p>
        Kloondetectie (Bevinding 4) vereist dat de opgeslagen teller niet nul is; een authenticator
        die we permanent taggen als <code>0</code> is permanent uitgesloten van op tellers gebaseerde bescherming.
      </p>
      <p><strong>De oplossing:</strong> De handtekeningteller wordt altijd geschreven. De kloondetectiedrempel verwerkt de interpretatie.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bevinding 6 &mdash; Ingetrokken passkey kon opnieuw worden geregistreerd <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categorie: Authenticatie</strong></p>
      <p>
        Wanneer een referentie als ingetrokken werd gemarkeerd (kloondetectie getriggerd), was er in
        het registratiepad geen controle die herregistratie van dezelfde <code>credential_id</code>
        voorkwam. Een tegenstander met de ruwe passkey-referentie en toegang tot het account kon de
        ingetrokken referentie opnieuw registreren, waardoor de gecompromitteerde geschiedenis werd gewist.
      </p>
      <p>
        Intrekking is alleen zinvol als het permanent is. Als het overschreven kan worden via
        herregistratie met dezelfde referentie, biedt kloondetectie geen blijvende bescherming.
      </p>
      <p><strong>De oplossing:</strong> Als <code>revoked_at</code> niet leeg is op een bestaand referentierecord,
      wordt herregistratie geblokkeerd met HTTP 403 en wordt een beveiligingslogvermelding geschreven.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bevinding 7 &mdash; Accountenumeratie via verschillende foutreacties <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categorie: Informatieverschaffing</strong></p>
      <p>
        Wanneer een passkey-login werd geprobeerd met een onbekend e-mailadres, nam de foutresponsebody
        een andere vorm aan dan andere mislukkingsgevallen — een leeg <code>[]</code> gegevens-payload
        versus de <code>{'error': 'passkey_invalid'}</code> body die elders werd geretourneerd. Een
        client die de API sondeerde, kon &ldquo;dit e-mailadres heeft geen account&rdquo; onderscheiden
        van &ldquo;dit e-mailadres bestaat maar de challenge mislukte&rdquo; door de responsebody te inspecteren.
      </p>
      <p>
        Bovendien werd het ruwe e-mailadres geschreven naar het observability-log. Log-aggregatiepijplijnen
        mogen nooit ruwe e-mailadressen van gebruikers bevatten — als het logsysteem wordt gecompromitteerd,
        wordt elke enumeratiepoging een lijst met e-mailadressen.
      </p>
      <p><strong>De oplossing:</strong> Zowel &ldquo;e-mail niet gevonden&rdquo; als &ldquo;geen referenties geregistreerd&rdquo;
      retourneren nu dezelfde foutbody. Het observability-log registreert alleen een SHA-256-hash van het
      e-mailadres — voldoende voor incidentcorrelatie, onvoldoende om het adres te reconstrueren.</p>
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
      <h2>Bevinding 8 &mdash; Herstelsleutel DB-status geschreven vóór bevestiging e-mailbezorging <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categorie: Gegevensintegriteit</strong></p>
      <p>
        Bij het genereren van accountherstelsleutels schreef de server <code>recovery_key_generated = 1</code>
        en <code>recovery_proof_key</code> naar het gebruikersrecord <em>vóór</em> het verzenden van
        de herstelsleutel-e-mail:
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
        Als het e-mailbericht niet kon worden verzonden, zou de database <code>recovery_key_generated = 1</code>
        tonen — het systeem gelooft dat een sleutel is uitgegeven. De gebruiker heeft hem nooit ontvangen.
      </p>
      <p>
        Er is geen regeneratiepad voor een gebruiker in deze staat. Accountherstel is permanent verbroken
        voor dat account totdat er handmatige interventie plaatsvindt.
      </p>
      <p><strong>De oplossing:</strong> E-mailbezorging wordt eerst bevestigd. De databasestatus weerspiegelt wat er werkelijk is gebeurd.</p>
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
      <h2>Bevinding 9 &mdash; Uitgeschakeld registratiepad verzamelde nog steeds wachtwoordvelden <span class="doc-badge low">Low</span></h2>
      <p><strong>Categorie: Aanvalsoppervlak</strong></p>
      <p>
        <code>RegistrationController</code> las nog steeds <code>password</code> en
        <code>confirm_password</code> uit POST, ook al was op wachtwoord gebaseerde registratie
        uitgeschakeld. PayCal-registratie is uitsluitend via passkey.
      </p>
      <p>
        Het verzamelen van velden die geen doel dienen is niet onschadelijk. Elke waarde die uit
        gebruikersinvoer wordt gelezen, is een oppervlak: het kan worden gelogd, geaudit, per ongeluk
        doorgegeven aan andere functies of opgenomen in foutpayloads. Het principe van minimaal oppervlak
        vereist dat we niet verzamelen wat we niet gebruiken.
      </p>
      <p><strong>De oplossing:</strong> Beide velden zijn verwijderd uit de invoerverzamelingskaart.</p>
    </section>

    <section class="doc-section">
      <h2>Bevinding 10 &mdash; Gebruikers-e-mail in de 403-respons van e-mailverificatie <span class="doc-badge low">Low</span></h2>
      <p><strong>Categorie: Informatieverschaffing</strong></p>
      <p>
        <code>EmailVerificationGuard</code> — de middleware die e-mailverificatie afdwingt vóór
        toegang tot beveiligde bronnen — bevatte <code>user_email</code> in de 403-responsebody:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        Als een aanvaller een geldig maar niet-geverifieerd sessietoken verkrijgt (via sessiefixatie
        of een gecompromitteerde tijdelijke link), kan hij het e-mailadres dat aan het account is
        gekoppeld leren uit de 403-responsebody — zonder het e-mailadres zelf te hebben opgegeven.
        De enige partij die baat heeft bij het e-mailadres in dit foutpayload is iemand die het
        sessietoken maar niet het e-mailadres heeft.
      </p>
      <p><strong>De oplossing:</strong> Het e-mailveld is verwijderd uit het foutpayload.</p>
    </section>

    <section class="doc-section">
      <h2>Bevinding 11 &mdash; Dode code in <code>EmailGarum::verifyNewUserEmail()</code> <span class="doc-badge low">Low</span></h2>
      <p><strong>Categorie: Dode code / Aanvalsoppervlak</strong></p>
      <p>
        <code>EmailGarum</code> bevatte een 90-regelige methode, <code>verifyNewUserEmail()</code>,
        die een op wachtwoord gebaseerde e-mailwijzigingsstroom afhandelde. Deze stroom werd vervangen
        toen het platform overschakelde op uitsluitend passkey-gebaseerde authenticatie. De methode
        werd nergens in de codebase aangeroepen.
      </p>
      <p>
        Dode code is niet neutraal. Het neemt ruimte in op het beveiligingsbeoordelingsoppervlak, in
        statische analyse en in de cognitieve belasting van iedereen die het bestand leest. Het
        vertegenwoordigt ook het risico dat een toekomstige ontwikkelaar, die niet weet dat het
        opzettelijk was verlaten, het zonder volledige context aan een nieuwe stroom kan koppelen.
      </p>
      <p><strong>De oplossing:</strong> Verwijderd. Alle aanroepplaatsen werden bevestigd leeg te zijn vóór verwijdering.</p>
    </section>

    <section class="doc-section">
      <h2>Overzicht van alle bevindingen</h2>
      <table class="doc-table" aria-label="Overzicht van alle bevindingen">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Bevinding</th>
            <th scope="col">Ernst</th>
            <th scope="col">Categorie</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td>Niet-atomaire <code>hset + expire</code> op 9 aanroepplaatsen</td><td><span class="doc-badge high">High</span></td><td>Redis / Atomiciteit</td></tr>
          <tr><td>2</td><td><code>del()</code> met leesreplica voor sleutelenumeratie</td><td><span class="doc-badge high">High</span></td><td>Redis / Uitloggen, CSRF</td></tr>
          <tr><td>3</td><td>WebAuthn UV-bypass bij accountherstelregistratie</td><td><span class="doc-badge high">High</span></td><td>Authenticatie</td></tr>
          <tr><td>4</td><td>Kloondetectie via handtekeningteller miste herhalingsaanvallen</td><td><span class="doc-badge medium">Medium</span></td><td>Authenticatie</td></tr>
          <tr><td>5</td><td>Handtekeningteller niet bewaard wanneer authenticator nul retourneert</td><td><span class="doc-badge medium">Medium</span></td><td>Authenticatie</td></tr>
          <tr><td>6</td><td>Ingetrokken passkey kon opnieuw worden geregistreerd</td><td><span class="doc-badge medium">Medium</span></td><td>Authenticatie</td></tr>
          <tr><td>7</td><td>Accountenumeratie via foutbody + ruw e-mailadres in logs</td><td><span class="doc-badge medium">Medium</span></td><td>Informatieverschaffing</td></tr>
          <tr><td>8</td><td>Herstelsleutel DB-status geschreven vóór e-mailbevestiging</td><td><span class="doc-badge medium">Medium</span></td><td>Gegevensintegriteit</td></tr>
          <tr><td>9</td><td>Uitgeschakelde registratie verzamelde nog steeds wachtwoordvelden</td><td><span class="doc-badge low">Low</span></td><td>Aanvalsoppervlak</td></tr>
          <tr><td>10</td><td>Gebruikers-e-mail in de 403-respons van e-mailverificatie</td><td><span class="doc-badge low">Low</span></td><td>Informatieverschaffing</td></tr>
          <tr><td>11</td><td>Dode methode <code>verifyNewUserEmail()</code> in EmailGarum</td><td><span class="doc-badge low">Low</span></td><td>Dode code</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>Wat we goed hebben gedaan</h2>
      <p>In het belang van een volledig beeld — de al aanwezige grondslagen:</p>
      <ul class="doc-list">
        <li>
          <strong>Passkey-first authenticatie.</strong> Het platform draait op WebAuthn zonder
          wachtwoord-fallback voor passkey-gebruikers. De UV-bypass en kloondetectieproblemen waren
          gebreken binnen een fundamenteel degelijke architectuur.
        </li>
        <li>
          <strong>Eenmalige capaciteitstokens.</strong> Mutaties op beheerderniveau vereisten al
          frisse, tijdgebonden tokens. De atomiciteitsoplossing versterkte een bestaande bescherming
          in plaats van een ontbrekende toe te voegen.
        </li>
        <li>
          <strong>Ondertekend beveiligingslog.</strong> Elke beveiligingsgebeurtenis — inclusief de
          nieuwe <code>passkey_revoked_reregistration_blocked</code>-gebeurtenissen toegevoegd in
          deze commit — wordt geschreven in een ondertekend, alleen-toevoegen log met gestructureerde velden.
        </li>
        <li>
          <strong>PHPStan op Niveau 9.</strong> Alle 11 gewijzigde bestanden werden gevalideerd op
          maximale statische analysestrengheid. De volledige regressiesuite slaagde zonder regressies.
        </li>
        <li>
          <strong>Kloondetectie bestond.</strong> De logica was aanwezig en gedeeltelijk correct.
          Bevinding 4 was een grensconditiefouten, geen ontbrekende functie.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Impact op klanten</h2>
      <ul class="doc-list">
        <li><strong>Geen bewijs van misbruik.</strong> Alle bevindingen werden intern geïdentificeerd via routinematige codebeoordeling. Geen extern rapport, CVE of incident ging vooraf aan deze bekendmaking.</li>
        <li><strong>Geen blootstelling van referenties in platte tekst.</strong> Geen wachtwoorden of herstelsleutels zijn blootgesteld. Referentiegegevens in rust blijven versleuteld. Biometrische gegevens verlaten nooit het authenticatorapparaat en worden nooit door PayCal verzonden of opgeslagen.</li>
        <li><strong>Geen bewijs van ongeautoriseerde accounttoegang.</strong> Beveiligingslogs tonen geen afwijkende patronen die consistent zijn met misbruik van deze vectoren.</li>
        <li><strong>Alle bevindingen hersteld vóór bekendmaking.</strong> Elk probleem beschreven in dit artikel werd opgelost, gecommit en getest voordat deze pagina werd gepubliceerd.</li>
        <li><strong>Volledige regressiesuite gevalideerd.</strong> Volledige PHPUnit-suite en PHPStan Niveau 9 statische analyse na herstel schoon voltooid.</li>
        <li><strong>Monitoring uitgebreid.</strong> Nieuwe beveiligingsloggebeurtenissen zijn toegevoegd voor handhaving van passkey-intrekking (Bevinding 6) om toekomstige anomalieën eerder te ontdekken.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Preventie en terugkeercontroles</h2>
      <p>Twee engineeringregels aangenomen als permanent beleid vanaf deze audit:</p>
      <div class="subject-example-cutout" role="note" aria-label="Nieuwe engineeringregel: hsetex als standaard Redis-schrijfpatroon">
        <h3><code>hsetex</code> is het standaard Redis-schrijfpatroon</h3>
        <p>
          Toekomstige code die een hash met een TTL moet schrijven, moet <code>Database::hsetex()</code>
          gebruiken. Het oude tweestapspatroon is niet langer toegestaan. Er worden PHPStan-regels
          geschreven om nieuwe voorvallen te markeren.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="Nieuwe engineeringregel: schrijfinstantie-prioriteit voor alle sleutelbewerkingen">
        <h3>Schrijfinstantie-prioriteit voor alle sleutelbewerkingen</h3>
        <p>
          Elke Redis-bewerking waarvan de correctheid afhankelijk is van het opnieuw lezen van wat
          zojuist is geschreven, moet de schrijfinstantie gebruiken. Leesr replica's zijn alleen voor
          niet-kritieke, hoogfrequente leesquery's.
        </p>
      </div>
      <p>
        Zelf-audits op dit specificiteitssniveau zijn een voortdurende verplichting. We zullen
        blijven publiceren wat we vinden. Toekomstige rapporten worden gepubliceerd op de
        <a href="<?php echo transparency_href('/transparency/'); ?>">Transparantiehub</a>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Bekendmakingstijdlijn</h2>
      <table class="doc-table" aria-label="Bekendmakingstijdlijn">
        <thead>
          <tr>
            <th scope="col">Datum</th>
            <th scope="col">Gebeurtenis</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">12 mei 2026</time></td>
            <td>Bevindingen geïdentificeerd tijdens een routinematige interne auditsessie</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 mei 2026</time></td>
            <td>Alle oplossingen geïmplementeerd en gecommit (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 mei 2026</time></td>
            <td>Volledige PHPUnit-regressiesuite geslaagd, PHPStan Niveau 9 schoon</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 mei 2026</time></td>
            <td>Gepushed naar origin/main</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 mei 2026</time></td>
            <td>Dit transparantie-artikel gepubliceerd</td>
          </tr>
        </tbody>
      </table>
      <p>
        Alle bevindingen werden intern geïdentificeerd. Geen extern rapport, CVE of inbreuk ging
        vooraf aan deze bekendmaking. Er is geen bewijs dat een van de bevindingen is misbruikt.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
