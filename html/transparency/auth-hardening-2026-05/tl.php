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
$pageTitle = 'Pagpapatibay ng Auth, Passkey at Redis — Mayo 2026 - [PayCal]';
$pageLabel = 'Pagpapatibay ng Auth, Passkey & Redis — Mayo 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Pagpapatibay ng Auth, Passkey &amp; Redis — Mayo 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Pagpapatibay ng Auth, Passkey &amp; Redis — Mayo 2026</h1>
    <p class="deck">
      Noong Mayo 12, 2026, nagsagawa kami ng panloob na pag-audit ng aming imprastraktura ng
      pagpapatunay, passkey, at Redis. Nahanap namin ang labing-isang isyu — lahat nasa code na
      aming isinulat mismo. Idinisenyo ng artikulong ito ang aming natuklasan, kung bakit ito
      mahalaga, at kung ano ang eksaktong binago namin.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Buod ng Ehekutibo</h2>
      <table class="doc-table" aria-label="Buod ng mga natuklasan sa pag-audit">
        <tbody>
          <tr>
            <td><strong>Petsa ng Pag-audit</strong></td>
            <td>Mayo 12, 2026</td>
          </tr>
          <tr>
            <td><strong>Saklaw</strong></td>
            <td>Pagpapatunay, passkey (WebAuthn), at imprastraktura ng Redis</td>
          </tr>
          <tr>
            <td><strong>Kabuuang Natuklasan</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Pamamahagi ayon sa Kalubhaan</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Katayuan ng Remedyasyon</strong></td>
            <td>Lahat ng natuklasan ay nalutas sa commit <code>493d5e44</code>. Buong test suite ay pumasa. Walang regressyon.</td>
          </tr>
          <tr>
            <td><strong>Katibayan ng Pagsasamantala</strong></td>
            <td>Wala</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Bakit Namin Ito Inilalabas</h2>
      <p>
        Natuklasan namin ang mga isyung ito sa aming sariling application code at mga layer ng
        imprastraktura — hindi sa mga third-party na dependency o mga external na serbisyo. Code na
        aming sinuri, na-commit, at naihatid.
      </p>
      <p>
        Inilalabas namin ito dahil ang transparency sa seguridad ay nangangailangan ng higit pa sa
        pagbubunyag ng mga external na CVE o pagpasa sa mga pag-audit. Nangangahulugan itong
        publikong pananagutan kapag ang aming sariling koponan ay naghatid ng code na hindi
        nakakatugon sa pamantayang itinakda namin para sa aming sarili.
      </p>
      <p>
        Hindi kami nahihiya dito. Ang mas malaking pagkabigo ay ang matuklasan ang mga isyung ito
        at piliin na huwag itong ibunyag.
      </p>
    </section>

    <section class="doc-section">
      <h2>Pamamaraan ng Pag-audit</h2>
      <p>
        Ang pag-auditng ito ay isinagawa nang panloob ng koponan ng engineering noong Mayo 12, 2026.
        Ang pagsusuri ay sumasaklaw sa lahat ng mga landas ng code na may kaugnayan sa pamamahala ng
        estado ng pagpapatunay, lifecycle ng WebAuthn credentials, at pamamahala ng Redis keys.
      </p>
      <ul class="doc-list">
        <li><strong>Manu-manong pagsusuri ng code</strong> ng lahat ng controller, domain, at imprastraktura file na kasangkot sa paglikha ng session, pagpaparehistro ng passkey, pag-login ng passkey, at mga daloy ng pagbawi ng account.</li>
        <li><strong>Static na pagsusuri</strong> sa pamamagitan ng PHPStan Antas 9 — zero tolerance para sa mga type-unsafe o hindi maaabot na landas ng code.</li>
        <li><strong>Pagmomodelo ng banta</strong> laban sa WebAuthn Antas 2 na detalye (§6.1 authenticator data, §7.1 registration ceremony, §7.2 authentication ceremony).</li>
        <li><strong>Regression testing</strong> gamit ang buong PHPUnit regression suite pagkatapos ng remedyasyon. Lahat ng test ay pumasa.</li>
      </ul>
      <p>Walang external na auditor, bug bounty report, o insidente sa seguridad ang nauna sa pagsusuring ito. Ang mga isyung ito ay natukoy sa pamamagitan ng isang rutinang panloob na proseso.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Aming Pilosopiya sa Engineering</h2>
      <p>Ang pag-audit na ito ay nagbunyag ng mga pagkabigo sa tatlong prinsipyo na itinuturing naming pundamental:</p>
      <ul class="doc-list">
        <li>
          <strong>Atomicity bago ang kawastuhan.</strong> Kung dalawang operasyon ang kailangang
          mangyari nang sabay-sabay, tratuhin sila bilang isang operasyon o huwag nang subukan ang
          disenyo. Ang isang sistema na &ldquo;tama sa karamihan ng oras&rdquo; ay hindi tama.
        </li>
        <li>
          <strong>Pinagsanib na depensa.</strong> Walang iisang kontrol ang dapat maging nag-iisang
          hadlang sa isang hangganan ng seguridad. Kung ang database ay nagtatanda ng isang kredensyal
          bilang binawi, ang landas ng pagpaparehistro ay dapat din itong ipatupad. Ang depensa ay
          hindi dapat magkaroon ng mga puwang sa pagitan ng mga layer.
        </li>
        <li>
          <strong>Asymmetry ng impormasyon bilang layunin ng disenyo.</strong> Ang isang umaatake na
          sumusubok sa sistema ay dapat matuto ng kaunti hangga't maaari tungkol sa nangyayari sa
          loob. Ang mga mensahe ng error, mga entry ng log, at mga oras ng tugon ay lahat ng mga
          ibabaw ng pagbubunyag.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Natuklasan 1 &mdash; Hindi Atomik na <code>hset + expire</code> (Redis Race Condition) <span class="doc-badge high">High</span></h2>
      <p><strong>Kategorya: Redis / Atomicity</strong></p>
      <p>
        Sa siyam na lugar ng tawag, ang isang Redis hash ay isinulat gamit ang <code>HSET</code> at
        pagkatapos ay kaagad binigyan ng TTL gamit ang isang hiwalay na <code>EXPIRE</code> na utos:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        Ito ay dalawang hiwalay na round trip sa Redis. Kung ang proseso ng PHP ay natapos, naputol,
        umabot sa timeout, o ang Redis ay nakaranas ng sandaling pagkabigo sa pagitan ng dalawa, ang
        hash ay naisulat nang walang expiry — at nabubuhay nang walang katiyakan sa Redis.
      </p>
      <p>Ang mga naapektuhang lugar ng tawag at ang kanilang mga implikasyon sa seguridad:</p>
      <table class="doc-table" aria-label="Mga naapektuhang lugar ng tawag para sa hindi atomik na hset+expire">
        <thead>
          <tr>
            <th scope="col">Lugar ng Tawag</th>
            <th scope="col">Uri ng Key</th>
            <th scope="col">Kahihinatnan ng Nawawalang TTL</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Rekord ng session</td>
            <td>Ang session ay hindi kailanman mag-e-expire — ang account ay maa-access nang higit sa nilalayon na buhay</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (enrollment challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Ang mga lumang datos ng challenge ay nananatili nang higit sa nilalayon na buhay, pinapataas ang panganib ng replay</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (register challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Katulad ng nasa itaas</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (login challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Katulad ng nasa itaas</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Recovery passkey challenge</td>
            <td>Ang datos ng session ng pagbawi ay hindi kailanman mag-e-expire</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (paglabas ng code)</td>
            <td>Recovery email code</td>
            <td>Ang mga one-time code ay mabubuhay nang higit sa nilalayon na window ng expiry</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (muling pagpapadala ng code)</td>
            <td>Recovery email code</td>
            <td>Katulad ng nasa itaas</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Mga one-time admin token</td>
            <td>Ang mga token na idinisenyo upang mag-expire sa 5 minuto ay maaaring mabuhay nang walang katiyakan</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Rekord ng transaksyon ng pagbawi</td>
            <td>Ang estado ng transaksyon ng pagbawi ay hindi kailanman nalilinis</td>
          </tr>
        </tbody>
      </table>
      <p>
        Para sa mga session, ito ay isang direktang paglabag sa buhay ng access. Ang isang session
        ay dapat magkaroon ng mahigpit na hangganan. Kung ang TTL ay hindi kailanman naitakda, hindi
        umiiral ang hangganan na iyon.
      </p>
      <p>
        Para sa mga one-time capability token, ang isang token na idinisenyo upang maging wasto nang
        eksaktong 300 segundo ay maaaring pa rin valid pagkatapos ng ilang araw.
      </p>
      <p><strong>Ang solusyon:</strong> Ipinakilala namin ang <code>Database::hsetex()</code> — isang wrapper na
      nagsasagawa ng parehong operasyon sa loob ng isang Redis <code>MULTI/EXEC</code> na transaksyon,
      na ginagawa silang atomik. Ang mga operasyon ay isinasagawa sa parehong yunit ng pagpapatupad, kaya ang
      key ay hindi maaaring umiral nang hindi inilalapat ang TTL nito. Ang key ay may data at TTL, o wala.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Bawat lugar ng tawag na naglalabas ng <code>hset</code> na sinusundan ng <code>expire</code> sa parehong key ay na-convert.</p>
    </section>

    <section class="doc-section">
      <h2>Natuklasan 2 &mdash; Ang Pag-logout at Invalidasyon ng CSRF ay Maaaring Tahimik na Mabigo <span class="doc-badge high">High</span></h2>
      <p><strong>Kategorya: Redis / Pag-logout, CSRF</strong></p>
      <p>
        Ang pamamaraan ng <code>Database::del()</code> — responsable sa pag-delete ng mga Redis key
        sa pamamagitan ng pattern — ay nag-enumerate ng mga key gamit ang <em>read replica</em> at
        pagkatapos ay naglabas ng mga utos na <code>DEL</code> sa <em>primary</em>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        Ang replication ng Redis ay asynchronous. Kung ang replica ay nahuhuli — kahit ilang
        millisecond — maaaring hindi pa nito naglalaman ang key na karanasang isinulat. Sa kasong
        iyon, ibinabalik ng <code>keys()</code> ang isang walang laman na listahan at walang
        <code>DEL</code> na inilabas sa primary. Ang key ay nabubuhay.
      </p>
      <p>Ang dalawang pinaka-kritikal na tumatawag ng <code>del()</code>:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — pag-logout:</strong> Kapag ang isang gumagamit ay
          mag-log out, tinatanggal namin ang kanyang session key. Kung ang replica ay nahuhuli, ang
          listahan ng mga session key ay nagbabalik ng walang laman, ang pagtanggal ay hindi kailanman
          na-trigger, at ang session ay nananatili sa primary. Naniniwala ang gumagamit na naka-log out
          siya. Hindi siya.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — invalidasyon ng nonce:</strong> Ang mga CSRF token
          ay mga one-time nonce. Pagkatapos ng unang paggamit ay dapat sila matanggal. Kung ang
          pagtanggal ay hindi kailanman na-trigger, ang token ay maaaring muling gamitin sa pangalawang
          kahilingan. Ang one-time ay nagiging reusable.
        </li>
      </ul>
      <p>
        Ang bug na ito ay banayad dahil nagpapakita lamang ito sa ilalim ng load o sa panahon ng
        pansamantalang pagkaantala ng replica. Sa pag-develop laban sa isang solong Redis instance,
        hindi ito kailanman na-trigger.
      </p>
      <p><strong>Ang solusyon:</strong> Ang pag-enumerate at pagtanggal ng key ay dapat na mag-target sa parehong instance.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Natuklasan 3 &mdash; Pag-bypass ng Beripikasyon ng Gumagamit ng WebAuthn <span class="doc-badge high">High</span></h2>
      <p><strong>Kategorya: Pagpapatunay</strong></p>
      <p>
        Sa <code>AccountRecoveryController</code>, kapag nagrerehistro ng isang passkey bilang bahagi
        ng pagbawi ng account, ang tawag sa <code>processCreate()</code> ay nagpapasa ng <code>false</code>
        para sa <code>requireUserVerification</code>:
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
        Ang challenge na ipinadala sa kliyente ay nagtatakda ng <code>userVerification: 'required'</code>
        — sinabihan ang authenticator na kailangang kumpletuhin ng gumagamit ang isang biometric o
        PIN na beripikasyon. Ngunit sa pag-verify ng tugon, sinasabi namin sa library na huwag ipatupad
        na nakatakda ang UV flag.
      </p>
      <p>
        Ang isang binagong kliyente ay maaaring magpadala ng tugon ng authenticator na may malinaw na
        UV bit. Tatanggapin ito ng aming server nang hindi nangangailangan na aktwal na naganap ang
        biometric na beripikasyon.
      </p>
      <p>
        Ang daloy ng pagbawi ng account ay ang landas na tinahak ng isang gumagamit kapag nawalan na
        siya ng access sa kanyang ibang mga kredensyal. Ito ang pinaka-mapanganib na ibabaw ng
        pagpapatunay na aming pinamamahalaan. Ang pagpapahina ng biometric enforcement dito ay
        eksaktong maling kompromiso.
      </p>
      <p><strong>Ang solusyon:</strong> Ang UV ay ipinapatupad na ngayon. Ang isang tugon kung saan ang authenticator data ay hindi nagdadala ng UV flag na nakatakda ay tinatanggihan.</p>
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
      <h2>Natuklasan 4 &mdash; Ang Clone Detection sa pamamagitan ng Sign Count ay Napalampas ang mga Replay Attack <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorya: Pagpapatunay</strong></p>
      <p>Ang aming passkey clone detection ay nagsusuri:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        Ang WebAuthn Antas 2 na detalye (§6.1) ay nagsasaad: kung ang nakaimbak na sign count ay
        hindi zero at ang bagong sign count ay hindi <em>mahigpit na mas malaki</em> kaysa sa
        nakaimbak na halaga, ang kredensyal ay dapat ituring na posibleng na-clone. Ang aming kondisyon
        ay nangangailangan ng <code>&lt;</code>, hindi <code>&lt;=</code>, kaya ang pantay na bilang
        — tulad ng sa isang replay attack — ay dumadaan nang hindi na-trigger ang clone flag.
      </p>
      <p><strong>Ang solusyon:</strong> Naka-align sa detalye.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Natuklasan 5 &mdash; Ang Sign Count ay Hindi Laging Nai-persist <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorya: Pagpapatunay</strong></p>
      <p>Pagkatapos ng matagumpay na pag-login ng passkey, ang pag-update ng sign count ay nakasalalay sa kung ito ay hindi zero:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Ang ilang authenticator ay nagbabalik ng <code>0</code> bilang sentinel na nangangahulugang
        &ldquo;ang device na ito ay hindi nagpapatupad ng counter.&rdquo; Kung ang isang device
        pagkatapos ay magsimulang magbalik ng tunay na counter (firmware update, o ang gumagamit ay
        nagrerehistro ng parehong kredensyal sa isang platform na sumusuporta sa mga counter), hindi
        namin kailanman ma-persist ang unang tunay na counter dahil naimbak namin ang <code>0</code>
        magpakailanman.
      </p>
      <p>
        Ang clone detection (Natuklasan 4) ay nangangailangan na ang nakaimbak na counter ay hindi
        zero; ang isang authenticator na permanente naming na-tag bilang <code>0</code> ay permanenteng
        hindi kasama sa proteksyong batay sa counter.
      </p>
      <p><strong>Ang solusyon:</strong> Ang sign count ay laging isinusulat. Ang threshold ng clone detection ang humahawak ng interpretasyon.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Natuklasan 6 &mdash; Ang Binawing Passkey ay Maaaring Muling Irehistro <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorya: Pagpapatunay</strong></p>
      <p>
        Kapag ang isang kredensyal ay natanda bilang binawi (na-trigger ang clone detection), walang
        tseke sa landas ng pagpaparehistro na pumipigil sa muling pagpaparehistro ng parehong
        <code>credential_id</code>. Ang isang kalaban na may hilaw na passkey credential at access
        sa account ay maaaring muling irehistro ang binawing kredensyal, na binubura ang kasaysayan
        ng kompromiso nito.
      </p>
      <p>
        Ang pagbawi ay mahalaga lamang kung ito ay permanente. Kung maaari itong ma-overwrite sa
        pamamagitan ng muling pagpaparehistro gamit ang parehong kredensyal, ang clone detection ay
        hindi nagbibigay ng anumang pangmatagalang proteksyon.
      </p>
      <p><strong>Ang solusyon:</strong> Kung ang <code>revoked_at</code> ay hindi walang laman sa isang umiiral na rekord ng kredensyal,
      ang muling pagpaparehistro ay hina-harang gamit ang HTTP 403 at isang entry ng log ng seguridad ay isinusulat.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Natuklasan 7 &mdash; Pag-enumerate ng Account sa pamamagitan ng Iba't Ibang Tugon ng Error <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorya: Pagsisiwalat ng Impormasyon</strong></p>
      <p>
        Kapag ang isang passkey login ay sinubukan gamit ang isang hindi kilalang email, ang katawan
        ng tugon ng error ay kumukuha ng ibang anyo mula sa ibang mga kaso ng pagkabigo — isang
        walang laman na payload ng datos <code>[]</code> kumpara sa katawan ng <code>{'error': 'passkey_invalid'}</code>
        na ibinibigay sa ibang lugar. Ang isang kliyente na sumusubok sa API ay maaaring makilala ang
        &ldquo;ang email na ito ay walang account&rdquo; mula sa &ldquo;ang email na ito ay umiiral
        ngunit nabigo ang challenge&rdquo; sa pamamagitan ng pagsusuri ng katawan ng tugon.
      </p>
      <p>
        Bukod dito, ang hilaw na email address ay isinusulat sa observability log. Ang mga pipeline
        ng pag-aggregate ng log ay hindi dapat maglaman ng mga hilaw na email address ng gumagamit —
        kung ang sistema ng log ay makompromiso, ang bawat pagtatangka ng enumeration ay nagiging
        listahan ng mga email.
      </p>
      <p><strong>Ang solusyon:</strong> Parehong &ldquo;email hindi nahanap&rdquo; at &ldquo;walang nakarehistrong kredensyal&rdquo;
      ay nagbabalik na ngayon ng parehong katawan ng error. Ang observability log ay nagtatala lamang ng SHA-256 hash ng
      email — sapat para sa pag-correlate ng insidente, hindi sapat upang muling buuin ang address.</p>
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
      <h2>Natuklasan 8 &mdash; Katayuan ng DB ng Recovery Key na Isinulat Bago Kumpirmahin ang Paghahatid ng Email <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategorya: Integridad ng Data</strong></p>
      <p>
        Sa panahon ng henerasyon ng recovery key ng account, isinusulat ng server ang
        <code>recovery_key_generated = 1</code> at <code>recovery_proof_key</code> sa rekord ng
        gumagamit <em>bago</em> magpadala ng recovery key email:
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
        Kung ang email ay nabigong maipadala, ipapakita ng database ang <code>recovery_key_generated = 1</code>
        — naniniwala ang sistema na inilabas ang isang key. Hindi ito kailanman natanggap ng gumagamit.
      </p>
      <p>
        Walang landas ng regenerasyon para sa isang gumagamit sa estadong ito. Ang pagbawi ng account
        ay permanenteng nasira para sa account na iyon hanggang sa manu-manong interbensyon.
      </p>
      <p><strong>Ang solusyon:</strong> Ang paghahatid ng email ay unang kumpirmado. Ang katayuan ng database ay sumasalamin sa kung ano ang aktwal na nangyari.</p>
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
      <h2>Natuklasan 9 &mdash; Ang Pinagandang Landas ng Pagpaparehistro ay Nangongolekta pa rin ng mga Password Field <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategorya: Attack Surface</strong></p>
      <p>
        Ang <code>RegistrationController</code> ay nagbabasa pa rin ng <code>password</code> at
        <code>confirm_password</code> mula sa POST kahit na ang pagpaparehistro batay sa password ay
        hindi pinagana. Ang pagpaparehistro sa PayCal ay eksklusibong sa pamamagitan ng passkey.
      </p>
      <p>
        Ang pag-collect ng mga field na walang layunin ay hindi inosente. Ang bawat halaga na nabasa
        mula sa input ng gumagamit ay isang ibabaw: maaari itong ma-log, ma-audit, mapuntahan nang
        hindi sinasadya sa ibang mga function, o isama sa mga payload ng error. Ang prinsipyo ng
        pinakamaliit na ibabaw ay nangangailangan na huwag kaming mangolekta ng hindi namin ginagamit.
      </p>
      <p><strong>Ang solusyon:</strong> Parehong field ay inalis mula sa mapa ng koleksyon ng input.</p>
    </section>

    <section class="doc-section">
      <h2>Natuklasan 10 &mdash; Email ng Gumagamit sa 403 na Tugon ng Beripikasyon ng Email <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategorya: Pagsisiwalat ng Impormasyon</strong></p>
      <p>
        Ang <code>EmailVerificationGuard</code> — ang middleware na nagpapatupad ng beripikasyon ng
        email bago maibigay ang access sa mga protektadong mapagkukunan — ay nagsasama ng
        <code>user_email</code> sa katawan ng 403 na tugon:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        Kung ang isang umaatake ay makakakuha ng isang valid ngunit hindi bine-verify na session token
        (sa pamamagitan ng session fixation o isang nakompromisong pansamantalang link), maaari niyang
        malaman ang email address na nauugnay sa account mula sa katawan ng 403 na tugon — nang hindi
        niya inibigay ang email mismo. Ang tanging partido na nakikinabang sa email sa payload ng error
        na ito ay ang isang tao na may session token ngunit walang email.
      </p>
      <p><strong>Ang solusyon:</strong> Ang field ng email ay inalis mula sa payload ng error.</p>
    </section>

    <section class="doc-section">
      <h2>Natuklasan 11 &mdash; Dead Code sa <code>EmailGarum::verifyNewUserEmail()</code> <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategorya: Dead Code / Attack Surface</strong></p>
      <p>
        Ang <code>EmailGarum</code> ay naglalaman ng isang 90-linya na pamamaraan,
        <code>verifyNewUserEmail()</code>, na humahawak ng isang daloy ng pagbabago ng email batay
        sa password. Ang daloy na ito ay pinalitan nang lumipat ang platform sa pagpapatunay na
        eksklusibong batay sa passkey. Ang pamamaraan ay hindi tinatawag kahit saan sa codebase.
      </p>
      <p>
        Ang dead code ay hindi neutral. Sumasakop ito ng espasyo sa ibabaw ng pagsusuri sa seguridad,
        sa static analysis, at sa cognitive load ng sinumang nagbabasa ng file. Nagdudulot din ito
        ng panganib na ang isang hinaharap na developer, na hindi alam na ito ay sadyang inabandona,
        ay maaaring ikonekta ito sa isang bagong daloy nang walang kumpletong konteksto.
      </p>
      <p><strong>Ang solusyon:</strong> Inalis. Lahat ng lugar ng tawag ay nakumpirma na walang laman bago ang pag-alis.</p>
    </section>

    <section class="doc-section">
      <h2>Buod ng Lahat ng Natuklasan</h2>
      <table class="doc-table" aria-label="Buod ng lahat ng natuklasan">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Natuklasan</th>
            <th scope="col">Kalubhaan</th>
            <th scope="col">Kategorya</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td>Hindi atomik na <code>hset + expire</code> sa 9 lugar ng tawag</td><td><span class="doc-badge high">High</span></td><td>Redis / Atomicity</td></tr>
          <tr><td>2</td><td><code>del()</code> gamit ang read replica para sa pag-enumerate ng key</td><td><span class="doc-badge high">High</span></td><td>Redis / Pag-logout, CSRF</td></tr>
          <tr><td>3</td><td>WebAuthn UV bypass sa pagpaparehistro ng pagbawi ng account</td><td><span class="doc-badge high">High</span></td><td>Pagpapatunay</td></tr>
          <tr><td>4</td><td>Clone detection sa pamamagitan ng sign count ay napalampas ang mga replay attack</td><td><span class="doc-badge medium">Medium</span></td><td>Pagpapatunay</td></tr>
          <tr><td>5</td><td>Sign count ay hindi nai-persist kapag ang authenticator ay nagbabalik ng zero</td><td><span class="doc-badge medium">Medium</span></td><td>Pagpapatunay</td></tr>
          <tr><td>6</td><td>Ang binawing passkey ay maaaring muling irehistro</td><td><span class="doc-badge medium">Medium</span></td><td>Pagpapatunay</td></tr>
          <tr><td>7</td><td>Pag-enumerate ng account sa pamamagitan ng katawan ng error + hilaw na email sa mga log</td><td><span class="doc-badge medium">Medium</span></td><td>Pagsisiwalat ng Impormasyon</td></tr>
          <tr><td>8</td><td>Katayuan ng DB ng recovery key na isinulat bago ang kumpirmasyon ng email</td><td><span class="doc-badge medium">Medium</span></td><td>Integridad ng Data</td></tr>
          <tr><td>9</td><td>Pinagandang pagpaparehistro ay nangongolekta pa rin ng mga password field</td><td><span class="doc-badge low">Low</span></td><td>Attack Surface</td></tr>
          <tr><td>10</td><td>Email ng gumagamit sa 403 na tugon ng beripikasyon ng email</td><td><span class="doc-badge low">Low</span></td><td>Pagsisiwalat ng Impormasyon</td></tr>
          <tr><td>11</td><td>Dead na pamamaraan <code>verifyNewUserEmail()</code> sa EmailGarum</td><td><span class="doc-badge low">Low</span></td><td>Dead Code</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>Ano ang Aming Nagawa nang Tama</h2>
      <p>Sa interes ng kumpletong larawan — ang mga pundasyon na mayroon na:</p>
      <ul class="doc-list">
        <li>
          <strong>Pagpapatunay na passkey-first.</strong> Ang platform ay tumatakbo sa WebAuthn nang
          walang password fallback para sa mga gumagamit ng passkey. Ang UV bypass at mga isyu sa
          clone detection ay mga depekto sa loob ng isang pundamental na matibay na arkitektura.
        </li>
        <li>
          <strong>Mga one-time capability token.</strong> Ang mga mutasyon sa antas ng admin ay
          nangangailangan na ng mga sariwang, limitadong oras na token. Ang pag-aayos ng atomicity
          ay nagpalakas ng isang umiiral na proteksyon sa halip na nagdagdag ng nawawalang isa.
        </li>
        <li>
          <strong>Nilagdaang security log.</strong> Ang bawat kaganapan sa seguridad — kasama ang mga
          bagong kaganapan na <code>passkey_revoked_reregistration_blocked</code> na idinagdag sa
          commit na ito — ay isinusulat sa isang nilagdaan, append-only na log na may mga structured field.
        </li>
        <li>
          <strong>PHPStan sa Antas 9.</strong> Lahat ng 11 binagong file ay napatunayan sa pinakamataas
          na katatagan ng static analysis. Ang buong regression suite ay pumasa nang walang regressyon.
        </li>
        <li>
          <strong>Ang clone detection ay umiiral.</strong> Ang lohika ay naroroon at bahagyang tama.
          Ang Natuklasan 4 ay isang pagkakamali sa kondisyon ng hangganan, hindi isang nawawalang tampok.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Epekto sa Customer</h2>
      <ul class="doc-list">
        <li><strong>Walang katibayan ng pagsasamantala.</strong> Lahat ng natuklasan ay natukoy nang panloob sa pamamagitan ng rutinang pagsusuri ng code. Walang external na ulat, CVE, o insidente ang nauna sa pagbubunyag na ito.</li>
        <li><strong>Walang pagbubunyag ng plaintext na kredensyal.</strong> Walang password o recovery key ang nabunyag. Ang mga datos ng kredensyal sa pahinga ay nananatiling naka-encrypt. Ang biometric data ay hindi kailanman umalis sa authenticator device at hindi kailanman ipinadala o inimbak ng PayCal.</li>
        <li><strong>Walang katibayan ng hindi awtorisadong access sa account.</strong> Ang mga security log ay walang ipinapakitang hindi pangkaraniwang pattern na naaayon sa pagsasamantala ng mga vector na ito.</li>
        <li><strong>Lahat ng natuklasan ay naayos bago ang pagbubunyag.</strong> Ang bawat isyu na inilarawan sa artikulong ito ay naayos, na-commit, at nasubok bago mailathala ang pahinang ito.</li>
        <li><strong>Buong regression suite na napatunayan.</strong> Buong PHPUnit suite at PHPStan Antas 9 static analysis ay matagumpay na natapos pagkatapos ng remedyasyon.</li>
        <li><strong>Pinalawak na pagsubaybay.</strong> Mga bagong kaganapan ng security log ay idinagdag para sa pagpapatupad ng pagbawi ng passkey (Natuklasan 6) upang mas maaga na matukoy ang mga hinaharap na anomalya.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Pag-iwas at Mga Kontrol sa Pagtái</h2>
      <p>Dalawang panuntunang engineering na pinagtibay bilang permanenteng patakaran mula sa pag-audit na ito:</p>
      <div class="subject-example-cutout" role="note" aria-label="Bagong panuntunan sa engineering: hsetex bilang default na pattern ng pagsulat ng Redis">
        <h3><code>hsetex</code> ang default na pattern ng pagsulat ng Redis</h3>
        <p>
          Ang anumang hinaharap na code na kailangang magsulat ng hash na may TTL ay dapat gumamit ng
          <code>Database::hsetex()</code>. Ang lumang dalawang-hakbang na pattern ay hindi na pinahihintulutan.
          Mga panuntunan ng PHPStan ang isusulat upang ma-flag ang mga bagong paglitaw.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="Bagong panuntunan sa engineering: primacy ng write instance para sa lahat ng operasyon ng key">
        <h3>Primacy ng Write Instance para sa Lahat ng Operasyon ng Key</h3>
        <p>
          Ang anumang operasyon ng Redis na ang kawastuhan ay nakasalalay sa muling pagbabasa ng kung
          ano ang karanasang isinulat ay dapat gumamit ng write instance. Ang mga read replica ay para
          lamang sa mga hindi kritikal na, high-read na query.
        </p>
      </div>
      <p>
        Ang mga self-audit sa antas ng detalye na ito ay isang patuloy na pangako. Patuloy naming
        ilalathala ang aming natutuklasan. Ang mga hinaharap na ulat ay ilalathala sa
        <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Takdang Oras ng Pagsisiwalat</h2>
      <table class="doc-table" aria-label="Takdang oras ng pagsisiwalat">
        <thead>
          <tr>
            <th scope="col">Petsa</th>
            <th scope="col">Kaganapan</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">Mayo 12, 2026</time></td>
            <td>Ang mga natuklasan ay natukoy sa panahon ng rutinang panloob na sesyon ng pag-audit</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">Mayo 12, 2026</time></td>
            <td>Lahat ng pag-aayos ay naipatupad at na-commit (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">Mayo 12, 2026</time></td>
            <td>Buong PHPUnit regression suite ay pumasa, PHPStan Antas 9 ay malinis</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">Mayo 12, 2026</time></td>
            <td>Na-push sa origin/main</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">Mayo 12, 2026</time></td>
            <td>Ang artikulong transparency na ito ay nailathala</td>
          </tr>
        </tbody>
      </table>
      <p>
        Lahat ng natuklasan ay natukoy nang panloob. Walang external na ulat, CVE, o paglabag ang
        nauna sa pagbubunyag na ito. Walang katibayan na ang alinman sa mga natuklasan ay nasamantalahan.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
