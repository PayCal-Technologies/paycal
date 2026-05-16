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
$pageTitle = 'Rafforzamento di Auth, Passkey e Redis — Maggio 2026 - [PayCal]';
$pageLabel = 'Rafforzamento di Auth, Passkey & Redis — Maggio 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Rafforzamento di Auth, Passkey &amp; Redis — Maggio 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Rafforzamento di Auth, Passkey &amp; Redis — Maggio 2026</h1>
    <p class="deck">
      Il 12 maggio 2026 abbiamo condotto un audit interno della nostra infrastruttura di
      autenticazione, passkey e Redis. Abbiamo trovato undici problemi, tutti in codice scritto
      da noi stessi. Questo articolo documenta ciò che abbiamo trovato, perché era importante
      e cosa abbiamo modificato esattamente.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Sommario esecutivo</h2>
      <table class="doc-table" aria-label="Sommario esecutivo dei risultati dell'audit">
        <tbody>
          <tr>
            <td><strong>Data dell'audit</strong></td>
            <td>12 maggio 2026</td>
          </tr>
          <tr>
            <td><strong>Ambito</strong></td>
            <td>Autenticazione, passkey (WebAuthn) e infrastruttura Redis</td>
          </tr>
          <tr>
            <td><strong>Risultati totali</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Distribuzione per gravità</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Stato della rimediazione</strong></td>
            <td>Tutti i risultati risolti nel commit <code>493d5e44</code>. Suite di test completa superata. Nessuna regressione.</td>
          </tr>
          <tr>
            <td><strong>Prove di sfruttamento</strong></td>
            <td>Nessuna</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Perché pubblichiamo questo</h2>
      <p>
        Abbiamo trovato questi problemi nel nostro codice applicativo e nei livelli infrastrutturali —
        non in dipendenze di terze parti o servizi esterni. Codice che abbiamo revisionato, committato
        e consegnato.
      </p>
      <p>
        Pubblichiamo questo perché la trasparenza nella sicurezza richiede più della divulgazione di
        CVE esterni o del superamento di audit. Significa essere pubblicamente responsabili quando il
        nostro team consegna codice che non soddisfa lo standard che ci siamo prefissati.
      </p>
      <p>
        Non ne siamo imbarazzati. Il fallimento più grave sarebbe stato scoprire questi problemi e
        scegliere di non divulgarli.
      </p>
    </section>

    <section class="doc-section">
      <h2>Metodologia di audit</h2>
      <p>
        Questo audit è stato condotto internamente dal team di ingegneria il 12 maggio 2026. La
        revisione ha coperto tutti i percorsi di codice relativi alla gestione dello stato di
        autenticazione, al ciclo di vita delle credenziali WebAuthn e alla gestione delle chiavi Redis.
      </p>
      <ul class="doc-list">
        <li><strong>Revisione manuale del codice</strong> di tutti i file controller, domain e infrastruttura coinvolti nella creazione di sessioni, registrazione passkey, login passkey e flussi di recupero account.</li>
        <li><strong>Analisi statica</strong> tramite PHPStan Livello 9 — tolleranza zero per percorsi di codice type-unsafe o irraggiungibili.</li>
        <li><strong>Modellazione delle minacce</strong> rispetto alla specifica WebAuthn Livello 2 (§6.1 dati autenticatore, §7.1 cerimonia di registrazione, §7.2 cerimonia di autenticazione).</li>
        <li><strong>Test di regressione</strong> con la suite di regressione PHPUnit completa dopo la rimediazione. Tutti i test superati.</li>
      </ul>
      <p>Nessun revisore esterno, report di bug bounty o incidente di sicurezza ha preceduto questa revisione. Questi problemi sono stati identificati tramite un processo interno di routine.</p>
    </section>

    <section class="doc-section highlight">
      <h2>La nostra filosofia di ingegneria</h2>
      <p>Questo audit ha rivelato fallimenti in tre principi che consideriamo fondamentali:</p>
      <ul class="doc-list">
        <li>
          <strong>L'atomicità prima della correttezza.</strong> Se due operazioni devono avvenire
          insieme, trattale come una sola operazione o non tentare il design. Un sistema che è
          &ldquo;corretto la maggior parte del tempo&rdquo; non è corretto.
        </li>
        <li>
          <strong>Difesa a livelli.</strong> Nessun singolo controllo dovrebbe essere l'unica barriera
          a un confine di sicurezza. Se il database contrassegna una credenziale come revocata, il
          percorso di registrazione deve applicarlo anch'esso. La difesa non deve avere lacune tra i livelli.
        </li>
        <li>
          <strong>L'asimmetria delle informazioni come obiettivo di progettazione.</strong> Un aggressore
          che sonda il sistema dovrebbe imparare il meno possibile su ciò che accade all'interno.
          I messaggi di errore, le voci di log e i tempi di risposta sono tutti superfici di esposizione.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Risultato 1 &mdash; <code>hset + expire</code> non atomico (Race Condition Redis) <span class="doc-badge high">High</span></h2>
      <p><strong>Categoria: Redis / Atomicità</strong></p>
      <p>
        In nove punti di chiamata, un hash Redis veniva scritto con <code>HSET</code> e poi
        immediatamente assegnato un TTL con un comando <code>EXPIRE</code> separato:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        Questi sono due round trip separati a Redis. Se il processo PHP termina, viene interrotto,
        raggiunge un timeout, o Redis sperimenta un guasto momentaneo tra di loro, l'hash viene
        scritto senza scadenza — e vive indefinitamente in Redis.
      </p>
      <p>I punti di chiamata interessati e le loro implicazioni di sicurezza:</p>
      <table class="doc-table" aria-label="Punti di chiamata interessati per hset+expire non atomico">
        <thead>
          <tr>
            <th scope="col">Punto di chiamata</th>
            <th scope="col">Tipo di chiave</th>
            <th scope="col">Conseguenza del TTL mancante</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Record di sessione</td>
            <td>La sessione non scade mai — account accessibile oltre la durata prevista</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (enrollment challenge)</td>
            <td>WebAuthn challenge</td>
            <td>I dati di challenge scaduti persistono oltre la durata prevista, aumentando il rischio di replay</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (register challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Come sopra</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (login challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Come sopra</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Recovery passkey challenge</td>
            <td>I dati di sessione di recupero non scadono mai</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (emissione codice)</td>
            <td>Codice email di recupero</td>
            <td>I codici monouso sopravvivono oltre la finestra di scadenza prevista</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (reinvio codice)</td>
            <td>Codice email di recupero</td>
            <td>Come sopra</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Token admin monouso</td>
            <td>I token progettati per scadere in 5 minuti possono sopravvivere indefinitamente</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Record di transazione di recupero</td>
            <td>Lo stato della transazione di recupero non viene mai ripulito</td>
          </tr>
        </tbody>
      </table>
      <p>
        Per le sessioni, questa è una violazione diretta della durata dell'accesso. Una sessione
        dovrebbe avere un limite rigido. Se il TTL non viene mai impostato, quel limite non esiste.
      </p>
      <p>
        Per i token di capacità monouso, un token progettato per essere valido esattamente 300 secondi
        può ancora essere valido giorni dopo.
      </p>
      <p><strong>La correzione:</strong> Abbiamo introdotto <code>Database::hsetex()</code> — un wrapper che esegue
      entrambe le operazioni all'interno di una transazione Redis <code>MULTI/EXEC</code>, rendendole atomiche.
      Le operazioni vengono eseguite nella stessa unità di esecuzione, quindi la chiave non può esistere senza
      che il suo TTL sia applicato. La chiave ha dati e TTL, oppure niente.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Ogni punto di chiamata che emetteva un <code>hset</code> seguito da <code>expire</code> sulla stessa chiave è stato convertito.</p>
    </section>

    <section class="doc-section">
      <h2>Risultato 2 &mdash; Il logout e l'invalidazione CSRF potevano fallire silenziosamente <span class="doc-badge high">High</span></h2>
      <p><strong>Categoria: Redis / Logout, CSRF</strong></p>
      <p>
        Il metodo <code>Database::del()</code> — responsabile dell'eliminazione delle chiavi Redis
        per pattern — enumerava le chiavi usando la <em>replica di lettura</em> e poi emetteva
        comandi <code>DEL</code> al <em>primario</em>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        La replica Redis è asincrona. Se la replica è in ritardo — anche di millisecondi — potrebbe
        non contenere ancora la chiave appena scritta. In quel caso, <code>keys()</code> restituisce
        una lista vuota e nessun <code>DEL</code> viene emesso al primario. La chiave sopravvive.
      </p>
      <p>I due chiamanti più critici di <code>del()</code>:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — logout:</strong> Quando un utente si disconnette,
          eliminiamo la sua chiave di sessione. Se la replica è in ritardo, la lista delle chiavi di
          sessione restituisce vuoto, l'eliminazione non viene mai attivata e la sessione continua
          ad esistere sul primario. L'utente crede di essersi disconnesso. Non è così.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — invalidazione del nonce:</strong> I token CSRF
          sono nonce monouso. Dopo il primo utilizzo devono essere eliminati. Se l'eliminazione non
          viene mai attivata, il token può essere riutilizzato in una seconda richiesta. Monouso
          diventa riutilizzabile.
        </li>
      </ul>
      <p>
        Questo bug è sottile perché si manifesta solo sotto carico o durante un ritardo temporaneo
        della replica. Nello sviluppo contro una singola istanza Redis, non si verifica mai.
      </p>
      <p><strong>La correzione:</strong> L'enumerazione e l'eliminazione delle chiavi devono puntare alla stessa istanza.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Risultato 3 &mdash; Bypass della verifica utente WebAuthn <span class="doc-badge high">High</span></h2>
      <p><strong>Categoria: Autenticazione</strong></p>
      <p>
        In <code>AccountRecoveryController</code>, durante la registrazione di una passkey nel contesto
        del recupero account, la chiamata a <code>processCreate()</code> passava <code>false</code>
        per <code>requireUserVerification</code>:
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
        La challenge inviata al client specificava <code>userVerification: 'required'</code> —
        all'autenticatore veniva detto che l'utente doveva completare una verifica biometrica o PIN.
        Ma durante la verifica della risposta, stavamo dicendo alla libreria di non imporre che il
        flag UV fosse impostato.
      </p>
      <p>
        Un client modificato potrebbe inviare una risposta dell'autenticatore con il bit UV cancellato.
        Il nostro server la accetterebbe senza richiedere che la verifica biometrica sia realmente avvenuta.
      </p>
      <p>
        Il flusso di recupero account è il percorso che un utente intraprende quando ha perso l'accesso
        alle sue altre credenziali. Questa è la superficie di autenticazione ad alto rischio che gestiamo.
        Indebolire l'imposizione biometrica qui è esattamente il compromesso sbagliato.
      </p>
      <p><strong>La correzione:</strong> UV è ora imposto. Una risposta in cui i dati dell'autenticatore non portano il flag UV impostato viene rifiutata.</p>
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
      <h2>Risultato 4 &mdash; Il rilevamento di cloni tramite contatore di firma mancava gli attacchi replay <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Autenticazione</strong></p>
      <p>Il nostro rilevamento di cloni di passkey verificava:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        La specifica WebAuthn Livello 2 (§6.1) afferma: se il contatore di firma memorizzato è diverso
        da zero e il nuovo contatore di firma non è <em>strettamente maggiore</em> del valore memorizzato,
        la credenziale deve essere considerata potenzialmente clonata. La nostra condizione richiedeva
        <code>&lt;</code>, non <code>&lt;=</code>, quindi un contatore uguale — come in un attacco replay
        — passava senza attivare il flag di clone.
      </p>
      <p><strong>La correzione:</strong> Allineato alle specifiche.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Risultato 5 &mdash; Il contatore di firma non veniva sempre persistito <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Autenticazione</strong></p>
      <p>Dopo un login passkey riuscito, l'aggiornamento del contatore di firma era condizionato al fatto che fosse diverso da zero:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Alcuni autenticatori restituiscono <code>0</code> come sentinel che significa &ldquo;questo
        dispositivo non implementa un contatore.&rdquo; Se un dispositivo inizia poi a restituire un
        contatore reale (aggiornamento firmware, o l'utente registra la stessa credenziale su una
        piattaforma che supporta i contatori), non avremmo mai persistito il primo contatore reale
        perché avevamo memorizzato <code>0</code> per sempre.
      </p>
      <p>
        Il rilevamento cloni (Risultato 4) richiede che il contatore memorizzato sia diverso da zero;
        un autenticatore che tagghiamo permanentemente come <code>0</code> è permanentemente escluso
        dalla protezione basata sui contatori.
      </p>
      <p><strong>La correzione:</strong> Il contatore di firma viene sempre scritto. La soglia di rilevamento cloni gestisce l'interpretazione.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Risultato 6 &mdash; Una passkey revocata poteva essere ri-registrata <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Autenticazione</strong></p>
      <p>
        Quando una credenziale era contrassegnata come revocata (rilevamento clone attivato), nel
        percorso di registrazione non c'era alcun controllo che impedisse la ri-registrazione dello
        stesso <code>credential_id</code>. Un avversario con la credenziale passkey grezza e l'accesso
        all'account potrebbe ri-registrare la credenziale revocata, cancellando la sua cronologia compromessa.
      </p>
      <p>
        La revoca è significativa solo se è permanente. Se può essere sovrascritta tramite ri-registrazione
        usando la stessa credenziale, il rilevamento cloni non fornisce alcuna protezione duratura.
      </p>
      <p><strong>La correzione:</strong> Se <code>revoked_at</code> non è vuoto su un record di credenziale esistente,
      la ri-registrazione viene bloccata con HTTP 403 e viene scritta una voce nel log di sicurezza.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Risultato 7 &mdash; Enumerazione account tramite risposte di errore differenti <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Divulgazione di informazioni</strong></p>
      <p>
        Quando veniva tentato un login passkey con un'email non riconosciuta, il corpo della risposta
        di errore prendeva una forma diversa dagli altri casi di fallimento — un payload di dati vuoto
        <code>[]</code> rispetto al corpo <code>{'error': 'passkey_invalid'}</code> restituito altrove.
        Un client che sondava l'API poteva distinguere &ldquo;questa email non ha un account&rdquo; da
        &ldquo;questa email esiste ma la challenge è fallita&rdquo; ispezionando il corpo della risposta.
      </p>
      <p>
        Inoltre, l'indirizzo email grezzo veniva scritto nel log di osservabilità. Le pipeline di
        aggregazione dei log non dovrebbero mai contenere indirizzi email utente grezzi — se il
        sistema di log viene compromesso, ogni tentativo di enumerazione diventa un elenco di email.
      </p>
      <p><strong>La correzione:</strong> Sia &ldquo;email non trovata&rdquo; che &ldquo;nessuna credenziale registrata&rdquo;
      restituiscono ora lo stesso corpo di errore. Il log di osservabilità registra solo un hash SHA-256
      dell'email — sufficiente per la correlazione degli incidenti, insufficiente per ricostruire l'indirizzo.</p>
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
      <h2>Risultato 8 &mdash; Stato DB chiave di recupero scritto prima della conferma di consegna email <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Integrità dei dati</strong></p>
      <p>
        Durante la generazione della chiave di recupero account, il server scriveva
        <code>recovery_key_generated = 1</code> e <code>recovery_proof_key</code> nel record utente
        <em>prima</em> di inviare l'email della chiave di recupero:
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
        Se l'email non riusciva ad essere inviata, il database mostrava <code>recovery_key_generated = 1</code>
        — il sistema crede che una chiave sia stata emessa. L'utente non l'ha mai ricevuta.
      </p>
      <p>
        Non esiste un percorso di rigenerazione per un utente in questo stato. Il recupero account
        è permanentemente rotto per quell'account fino a un intervento manuale.
      </p>
      <p><strong>La correzione:</strong> La consegna dell'email viene prima confermata. Lo stato del database riflette ciò che è effettivamente accaduto.</p>
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
      <h2>Risultato 9 &mdash; Il percorso di registrazione disabilitato raccoglieva ancora i campi password <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoria: Superficie di attacco</strong></p>
      <p>
        <code>RegistrationController</code> leggeva ancora <code>password</code> e
        <code>confirm_password</code> dal POST anche se la registrazione basata su password era
        disabilitata. La registrazione PayCal è esclusivamente tramite passkey.
      </p>
      <p>
        Raccogliere campi che non servono a nulla non è innocuo. Ogni valore letto dall'input utente
        è una superficie: può essere registrato, auditato, passato accidentalmente ad altre funzioni
        o incluso nei payload di errore. Il principio della superficie minima richiede che non
        raccogliamo ciò che non utilizziamo.
      </p>
      <p><strong>La correzione:</strong> Entrambi i campi sono stati rimossi dalla mappa di raccolta dell'input.</p>
    </section>

    <section class="doc-section">
      <h2>Risultato 10 &mdash; Email utente nella risposta 403 di verifica email <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoria: Divulgazione di informazioni</strong></p>
      <p>
        <code>EmailVerificationGuard</code> — il middleware che impone la verifica email prima di
        concedere l'accesso alle risorse protette — includeva <code>user_email</code> nel corpo della
        risposta 403:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        Se un attaccante ottiene un token di sessione valido ma non verificato (tramite session fixation
        o un link temporaneo compromesso), può apprendere l'indirizzo email associato all'account dal
        corpo della risposta 403 — senza aver fornito l'email lui stesso. L'unica parte che beneficia
        dell'email in questo payload di errore è qualcuno che ha il token di sessione ma non l'email.
      </p>
      <p><strong>La correzione:</strong> Il campo email è stato rimosso dal payload di errore.</p>
    </section>

    <section class="doc-section">
      <h2>Risultato 11 &mdash; Codice morto in <code>EmailGarum::verifyNewUserEmail()</code> <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoria: Codice morto / Superficie di attacco</strong></p>
      <p>
        <code>EmailGarum</code> conteneva un metodo di 90 righe, <code>verifyNewUserEmail()</code>,
        che gestiva un flusso di cambio email basato su password. Questo flusso è stato sostituito
        quando la piattaforma è passata all'autenticazione esclusivamente tramite passkey. Il metodo
        non veniva chiamato da nessuna parte nel codebase.
      </p>
      <p>
        Il codice morto non è neutro. Occupa spazio nella superficie di revisione della sicurezza,
        nell'analisi statica e nel carico cognitivo di chiunque legga il file. Presenta anche il
        rischio che uno sviluppatore futuro, non sapendo che era stato abbandonato intenzionalmente,
        possa collegarlo a un nuovo flusso senza contesto completo.
      </p>
      <p><strong>La correzione:</strong> Rimosso. Tutti i siti di chiamata sono stati confermati vuoti prima della rimozione.</p>
    </section>

    <section class="doc-section">
      <h2>Riepilogo di tutti i risultati</h2>
      <table class="doc-table" aria-label="Riepilogo di tutti i risultati">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Risultato</th>
            <th scope="col">Gravità</th>
            <th scope="col">Categoria</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td><code>hset + expire</code> non atomico in 9 siti di chiamata</td><td><span class="doc-badge high">High</span></td><td>Redis / Atomicità</td></tr>
          <tr><td>2</td><td><code>del()</code> che utilizza la replica di lettura per l'enumerazione delle chiavi</td><td><span class="doc-badge high">High</span></td><td>Redis / Logout, CSRF</td></tr>
          <tr><td>3</td><td>Bypass UV WebAuthn nella registrazione di recupero account</td><td><span class="doc-badge high">High</span></td><td>Autenticazione</td></tr>
          <tr><td>4</td><td>Rilevamento cloni tramite contatore di firma mancava gli attacchi replay</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticazione</td></tr>
          <tr><td>5</td><td>Contatore di firma non persistito quando l'autenticatore restituisce zero</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticazione</td></tr>
          <tr><td>6</td><td>Passkey revocata poteva essere ri-registrata</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticazione</td></tr>
          <tr><td>7</td><td>Enumerazione account via corpo errore + email grezza nei log</td><td><span class="doc-badge medium">Medium</span></td><td>Divulgazione di informazioni</td></tr>
          <tr><td>8</td><td>Stato DB chiave di recupero scritto prima della conferma email</td><td><span class="doc-badge medium">Medium</span></td><td>Integrità dei dati</td></tr>
          <tr><td>9</td><td>Registrazione disabilitata raccoglieva ancora i campi password</td><td><span class="doc-badge low">Low</span></td><td>Superficie di attacco</td></tr>
          <tr><td>10</td><td>Email utente nella risposta 403 di verifica email</td><td><span class="doc-badge low">Low</span></td><td>Divulgazione di informazioni</td></tr>
          <tr><td>11</td><td>Metodo morto <code>verifyNewUserEmail()</code> in EmailGarum</td><td><span class="doc-badge low">Low</span></td><td>Codice morto</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>Cosa abbiamo fatto bene</h2>
      <p>Nell'interesse di un quadro completo — le basi già in atto:</p>
      <ul class="doc-list">
        <li>
          <strong>Autenticazione passkey-first.</strong> La piattaforma funziona su WebAuthn senza
          fallback password per gli utenti passkey. Il bypass UV e i problemi di rilevamento cloni
          erano difetti all'interno di un'architettura fondamentalmente solida.
        </li>
        <li>
          <strong>Token di capacità monouso.</strong> Le mutazioni a livello admin richiedevano già
          token freschi e a tempo limitato. La correzione dell'atomicità ha rafforzato una protezione
          esistente piuttosto che aggiungerne una mancante.
        </li>
        <li>
          <strong>Log di sicurezza firmato.</strong> Ogni evento di sicurezza — inclusi i nuovi eventi
          <code>passkey_revoked_reregistration_blocked</code> aggiunti in questo commit — viene scritto
          in un log firmato, solo in aggiunta, con campi strutturati.
        </li>
        <li>
          <strong>PHPStan al Livello 9.</strong> Tutti gli 11 file modificati sono stati validati con
          il massimo rigore di analisi statica. La suite di regressione completa ha passato senza regressioni.
        </li>
        <li>
          <strong>Il rilevamento cloni esisteva.</strong> La logica era presente e parzialmente corretta.
          Il Risultato 4 era un errore di condizione limite, non una funzionalità mancante.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Impatto sui clienti</h2>
      <ul class="doc-list">
        <li><strong>Nessuna prova di sfruttamento.</strong> Tutti i risultati sono stati identificati internamente tramite revisione del codice di routine. Nessun report esterno, CVE o incidente ha preceduto questa divulgazione.</li>
        <li><strong>Nessuna esposizione di credenziali in chiaro.</strong> Nessuna password o chiave di recupero è stata esposta. I dati delle credenziali a riposo rimangono cifrati. I dati biometrici non lasciano mai il dispositivo autenticatore e non vengono mai trasmessi né memorizzati da PayCal.</li>
        <li><strong>Nessuna prova di accesso non autorizzato agli account.</strong> I log di sicurezza non mostrano pattern anomali coerenti con lo sfruttamento di questi vettori.</li>
        <li><strong>Tutti i risultati rimediati prima della divulgazione.</strong> Ogni problema descritto in questo articolo è stato corretto, committato e testato prima che questa pagina fosse pubblicata.</li>
        <li><strong>Suite di regressione completa validata.</strong> Suite PHPUnit completa e analisi statica PHPStan Livello 9 completate correttamente dopo la rimediazione.</li>
        <li><strong>Monitoraggio esteso.</strong> Nuovi eventi di log di sicurezza sono stati aggiunti per l'applicazione della revoca passkey (Risultato 6) per rilevare anomalie future prima.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Controlli di prevenzione e ricorrenza</h2>
      <p>Due regole di ingegneria adottate come politica permanente a partire da questo audit:</p>
      <div class="subject-example-cutout" role="note" aria-label="Nuova regola di ingegneria: hsetex come pattern di scrittura Redis predefinito">
        <h3><code>hsetex</code> è il pattern di scrittura Redis predefinito</h3>
        <p>
          Qualsiasi codice futuro che debba scrivere un hash con un TTL deve usare
          <code>Database::hsetex()</code>. Il vecchio pattern a due fasi non è più consentito.
          Verranno scritte regole PHPStan per segnalare le nuove occorrenze.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="Nuova regola di ingegneria: primato dell'istanza di scrittura per tutte le operazioni sulle chiavi">
        <h3>Primato dell'istanza di scrittura per tutte le operazioni sulle chiavi</h3>
        <p>
          Qualsiasi operazione Redis la cui correttezza dipende dalla rilettura di ciò che è appena
          stato scritto deve usare l'istanza di scrittura. Le repliche di lettura sono solo per
          query non critiche ad alta lettura.
        </p>
      </div>
      <p>
        Gli auto-audit a questo livello di specificità sono un impegno continuativo. Continueremo
        a pubblicare ciò che troviamo. I report futuri saranno pubblicati nell'
        <a href="<?php echo transparency_href('/transparency/'); ?>">Hub di trasparenza</a>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Cronologia della divulgazione</h2>
      <table class="doc-table" aria-label="Cronologia della divulgazione">
        <thead>
          <tr>
            <th scope="col">Data</th>
            <th scope="col">Evento</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">12 maggio 2026</time></td>
            <td>Risultati identificati durante una sessione di audit interno di routine</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 maggio 2026</time></td>
            <td>Tutte le correzioni implementate e committate (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 maggio 2026</time></td>
            <td>Suite di regressione PHPUnit completa superata, PHPStan Livello 9 pulito</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 maggio 2026</time></td>
            <td>Inviato a origin/main</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 maggio 2026</time></td>
            <td>Questo articolo di trasparenza pubblicato</td>
          </tr>
        </tbody>
      </table>
      <p>
        Tutti i risultati sono stati identificati internamente. Nessun report esterno, CVE o violazione
        ha preceduto questa divulgazione. Non esiste alcuna prova che uno qualsiasi dei risultati sia
        stato sfruttato.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
