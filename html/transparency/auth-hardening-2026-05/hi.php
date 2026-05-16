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
$pageTitle = 'Auth, Passkey और Redis सुदृढ़ीकरण — मई 2026 - [PayCal]';
$pageLabel = 'Auth, Passkey और Redis सुदृढ़ीकरण — मई 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Auth, Passkey &amp; Redis सुदृढ़ीकरण — मई 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Auth, Passkey &amp; Redis सुदृढ़ीकरण — मई 2026</h1>
    <p class="deck">
      12 मई 2026 को, हमने अपनी प्रमाणीकरण, पासकी और Redis अवसंरचना का एक आंतरिक ऑडिट किया।
      हमें ग्यारह समस्याएं मिलीं — सभी उस कोड में जो हमने स्वयं लिखा था। यह लेख दस्तावेज़ करता
      है कि हमें क्या मिला, यह क्यों महत्वपूर्ण था और हमने वास्तव में क्या बदला।
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>कार्यकारी सारांश</h2>
      <table class="doc-table" aria-label="ऑडिट निष्कर्षों का कार्यकारी सारांश">
        <tbody>
          <tr>
            <td><strong>ऑडिट तिथि</strong></td>
            <td>12 मई 2026</td>
          </tr>
          <tr>
            <td><strong>दायरा</strong></td>
            <td>प्रमाणीकरण, पासकी (WebAuthn) और Redis अवसंरचना</td>
          </tr>
          <tr>
            <td><strong>कुल निष्कर्ष</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>गंभीरता वितरण</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>उपचार स्थिति</strong></td>
            <td>सभी निष्कर्ष commit <code>493d5e44</code> में हल किए गए। पूर्ण परीक्षण सूट पास। कोई regression नहीं।</td>
          </tr>
          <tr>
            <td><strong>शोषण का प्रमाण</strong></td>
            <td>कोई नहीं</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>हम यह क्यों प्रकाशित कर रहे हैं</h2>
      <p>
        हमें ये समस्याएं अपने स्वयं के एप्लिकेशन कोड और अवसंरचना परतों में मिलीं — तृतीय-पक्ष
        निर्भरताओं या बाहरी सेवाओं में नहीं। वह कोड जिसे हमने समीक्षा की, commit किया और deliver किया।
      </p>
      <p>
        हम इसे प्रकाशित करते हैं क्योंकि सुरक्षा पारदर्शिता के लिए बाहरी CVE प्रकट करने या
        ऑडिट पास करने से अधिक की आवश्यकता है। इसका अर्थ है सार्वजनिक रूप से जवाबदेह होना जब
        हमारी अपनी टीम ऐसा कोड deliver करती है जो हमारे अपने निर्धारित मानक को पूरा नहीं करता।
      </p>
      <p>
        हमें इसकी शर्म नहीं है। अधिक गंभीर विफलता होती इन समस्याओं को खोजना और उन्हें न
        प्रकट करने का निर्णय लेना।
      </p>
    </section>

    <section class="doc-section">
      <h2>ऑडिट पद्धति</h2>
      <p>
        यह ऑडिट 12 मई 2026 को इंजीनियरिंग टीम द्वारा आंतरिक रूप से आयोजित किया गया था। समीक्षा
        में प्रमाणीकरण स्थिति प्रबंधन, WebAuthn credential जीवनचक्र और Redis key handling से
        संबंधित सभी कोड पथ शामिल थे।
      </p>
      <ul class="doc-list">
        <li><strong>मैनुअल कोड समीक्षा</strong> — session निर्माण, passkey पंजीकरण, passkey login और खाता पुनर्प्राप्ति प्रवाह में शामिल सभी controller, domain और infrastructure फ़ाइलों की।</li>
        <li><strong>स्थैतिक विश्लेषण</strong> PHPStan Level 9 के माध्यम से — type-unsafe या अप्राप्य कोड पथों के लिए शून्य सहनशीलता।</li>
        <li><strong>खतरा मॉडलिंग</strong> WebAuthn Level 2 specification (§6.1 authenticator data, §7.1 registration ceremony, §7.2 authentication ceremony) के विरुद्ध।</li>
        <li><strong>Regression testing</strong> उपचार के बाद पूर्ण PHPUnit regression suite के साथ। सभी परीक्षण पास।</li>
      </ul>
      <p>इस समीक्षा से पहले कोई बाहरी auditor, bug bounty रिपोर्ट या सुरक्षा घटना नहीं थी। ये समस्याएं एक नियमित आंतरिक प्रक्रिया द्वारा पहचानी गई थीं।</p>
    </section>

    <section class="doc-section highlight">
      <h2>हमारी इंजीनियरिंग दर्शन</h2>
      <p>इस ऑडिट ने तीन सिद्धांतों में विफलताओं को उजागर किया जिन्हें हम मौलिक मानते हैं:</p>
      <ul class="doc-list">
        <li>
          <strong>सुधार से पहले परमाणुता।</strong> यदि दो ऑपरेशन एक साथ होने चाहिए, तो उन्हें एक ऑपरेशन के रूप में
          मानें या डिज़ाइन का प्रयास ही न करें। एक प्रणाली जो &ldquo;अधिकांश समय सही&rdquo; है, सही नहीं है।
        </li>
        <li>
          <strong>स्तरित रक्षा।</strong> किसी सुरक्षा सीमा पर कोई एकल नियंत्रण एकमात्र बाधा नहीं होनी चाहिए।
          यदि डेटाबेस किसी credential को revoked के रूप में चिह्नित करता है, तो पंजीकरण पथ को भी इसे
          लागू करना होगा। रक्षा में परतों के बीच अंतराल नहीं होने चाहिए।
        </li>
        <li>
          <strong>सूचना असमानता एक डिज़ाइन लक्ष्य के रूप में।</strong> सिस्टम की जांच करने वाले हमलावर
          को यथासंभव कम सीखना चाहिए कि अंदर क्या हो रहा है। error messages, log entries और response
          timing सभी exposure surfaces हैं।
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>निष्कर्ष 1 &mdash; गैर-परमाणु <code>hset + expire</code> (Redis Race Condition) <span class="doc-badge high">High</span></h2>
      <p><strong>श्रेणी: Redis / परमाणुता</strong></p>
      <p>
        आठ call sites पर, एक Redis hash <code>HSET</code> के साथ लिखा गया था और फिर तुरंत एक
        अलग <code>EXPIRE</code> कमांड के साथ TTL असाइन किया गया था:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        ये Redis के दो अलग round trips हैं। यदि PHP process बाहर निकलता है, बाधित होता है, timeout
        हो जाता है, या Redis के बीच एक momentary failure अनुभव करता है, तो hash बिना expiration के
        लिखा जाता है — और Redis में अनिश्चित काल तक रहता है।
      </p>
      <p>प्रभावित call sites और उनके सुरक्षा निहितार्थ:</p>
      <table class="doc-table" aria-label="गैर-परमाणु hset+expire के लिए प्रभावित call sites">
        <thead>
          <tr>
            <th scope="col">Call Site</th>
            <th scope="col">Key Type</th>
            <th scope="col">TTL न होने का परिणाम</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Session record</td>
            <td>Session कभी expire नहीं होता — इच्छित lifetime से परे खाता सुलभ</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (enrollment challenge)</td>
            <td>WebAuthn challenge</td>
            <td>पुराने challenge data इच्छित lifetime से परे बने रहते हैं, replay जोखिम बढ़ाते हैं</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (register challenge)</td>
            <td>WebAuthn challenge</td>
            <td>उपरोक्त के समान</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (login challenge)</td>
            <td>WebAuthn challenge</td>
            <td>उपरोक्त के समान</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Recovery passkey challenge</td>
            <td>Recovery session data कभी expire नहीं होता</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (code issuance)</td>
            <td>Recovery email code</td>
            <td>One-time codes अपनी इच्छित expiry window से आगे survive करते हैं</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (code resend)</td>
            <td>Recovery email code</td>
            <td>उपरोक्त के समान</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Admin one-use tokens</td>
            <td>5 मिनट में expire होने के लिए डिज़ाइन किए गए tokens अनिश्चित काल तक survive कर सकते हैं</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Recovery transaction record</td>
            <td>Recovery transaction state कभी clean नहीं होता</td>
          </tr>
        </tbody>
      </table>
      <p>
        Sessions के लिए, यह access lifetime का सीधा उल्लंघन है। एक session में एक कठोर ceiling
        होनी चाहिए। यदि TTL कभी सेट नहीं होता, तो वह ceiling मौजूद नहीं है।
      </p>
      <p>
        One-use capability tokens के लिए, ठीक 300 सेकंड के लिए valid होने के लिए डिज़ाइन किया
        गया token दिनों बाद भी valid हो सकता है।
      </p>
      <p><strong>समाधान:</strong> हमने <code>Database::hsetex()</code> पेश किया — एक wrapper जो दोनों ऑपरेशनों को
      एक Redis <code>MULTI/EXEC</code> transaction के अंदर execute करता है, उन्हें atomic बनाता है।
      ऑपरेशन एक ही execution unit में run होते हैं, इसलिए key अपने TTL के बिना exist नहीं कर सकती।
      Key में या तो data और TTL है, या कुछ नहीं।</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>प्रत्येक call site जो एक ही key पर <code>hset</code> के बाद <code>expire</code> issue करता था, convert किया गया।</p>
    </section>

    <section class="doc-section">
      <h2>निष्कर्ष 2 &mdash; Logout और CSRF Invalidation चुपचाप विफल हो सकते थे <span class="doc-badge high">High</span></h2>
      <p><strong>श्रेणी: Redis / Logout, CSRF</strong></p>
      <p>
        <code>Database::del()</code> method — pattern द्वारा Redis keys हटाने के लिए जिम्मेदार —
        <em>read replica</em> का उपयोग करके keys enumerate करता था और फिर <em>primary</em> को
        <code>DEL</code> commands issue करता था:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        Redis replication asynchronous है। यदि replica पीछे है — यहाँ तक कि milliseconds से —
        तो इसमें वह key नहीं हो सकती जो अभी लिखी गई थी। उस स्थिति में, <code>keys()</code>
        खाली list return करता है और primary को कोई <code>DEL</code> issue नहीं होता। Key बच जाती है।
      </p>
      <p><code>del()</code> के दो सबसे critical callers:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — logout:</strong> जब user logout करता है, हम उसकी
          session key हटाते हैं। यदि replica पीछे है, session key list खाली return होती है, deletion
          कभी trigger नहीं होती, और session primary पर exist करती रहती है। User सोचता है वह logout
          हो गया है। वह नहीं हुआ।
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — nonce invalidation:</strong> CSRF tokens
          one-time nonces हैं। पहले उपयोग के बाद उन्हें delete किया जाना चाहिए। यदि deletion कभी
          trigger नहीं होती, token को दूसरे request में reuse किया जा सकता है। One-time reusable हो जाता है।
        </li>
      </ul>
      <p>
        यह bug subtle है क्योंकि यह केवल load के तहत या temporary replica lag के दौरान manifest
        होता है। एक single Redis instance के विरुद्ध development में, यह कभी trigger नहीं होता।
      </p>
      <p><strong>समाधान:</strong> Key enumeration और deletion को एक ही instance को target करना होगा।</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>निष्कर्ष 3 &mdash; WebAuthn User Verification Bypass <span class="doc-badge high">High</span></h2>
      <p><strong>श्रेणी: प्रमाणीकरण</strong></p>
      <p>
        <code>AccountRecoveryController</code> में, खाता पुनर्प्राप्ति के हिस्से के रूप में passkey
        register करते समय, <code>processCreate()</code> call ने <code>requireUserVerification</code>
        के लिए <code>false</code> pass किया:
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
        Client को issue किए गए challenge ने <code>userVerification: 'required'</code> specify किया
        था — authenticator को बताया गया कि user को biometric verification या PIN complete करना
        होगा। लेकिन response verify करते समय, हम library को बता रहे थे कि UV flag set है, यह enforce न करे।
      </p>
      <p>
        एक modified client UV bit cleared के साथ एक authenticator response submit कर सकता था।
        हमारा server इसे accept कर लेता बिना यह require किए कि biometric verification वास्तव में हुई हो।
      </p>
      <p>
        Account recovery flow वह path है जो user तब लेता है जब उसने अपने अन्य credentials तक
        access खो दिया है। यह वह उच्चतम-जोखिम authentication surface है जिसे हम operate करते हैं।
        यहाँ biometric enforcement को कमज़ोर करना बिल्कुल गलत tradeoff है।
      </p>
      <p><strong>समाधान:</strong> UV अब enforce किया जाता है। एक response जहाँ authenticator data UV flag set नहीं करता, reject किया जाता है।</p>
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
      <h2>निष्कर्ष 4 &mdash; Sign Count Clone Detection Replay Attacks चूक गई <span class="doc-badge medium">Medium</span></h2>
      <p><strong>श्रेणी: प्रमाणीकरण</strong></p>
      <p>हमारी passkey clone detection check करती थी:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        WebAuthn Level 2 specification (§6.1) कहती है: यदि stored signature counter non-zero है
        और नया signature counter stored value से <em>strictly greater</em> नहीं है, तो credential
        को potentially cloned माना जाना चाहिए। हमारी condition को <code>&lt;</code> की आवश्यकता
        थी, <code>&lt;=</code> की नहीं, इसलिए एक equal counter — जैसे replay attack में — clone
        flag trigger किए बिना pass हो जाता था।
      </p>
      <p><strong>समाधान:</strong> Specification के अनुसार align किया गया।</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>निष्कर्ष 5 &mdash; Sign Count हमेशा Persist नहीं किया जाता था <span class="doc-badge medium">Medium</span></h2>
      <p><strong>श्रेणी: प्रमाणीकरण</strong></p>
      <p>Successful passkey login के बाद, signature counter update इस पर conditioned था कि वह non-zero हो:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        कुछ authenticators <code>0</code> एक sentinel के रूप में return करते हैं जिसका अर्थ है
        &ldquo;यह device counter implement नहीं करता।&rdquo; यदि कोई device बाद में एक real
        counter return करना शुरू करता है (firmware update, या user उसी credential को counter-supporting
        platform पर register करता है), तो हम पहला real counter कभी persist नहीं करते क्योंकि
        हमने <code>0</code> हमेशा के लिए store किया था।
      </p>
      <p>
        Clone detection (निष्कर्ष 4) के लिए आवश्यक है कि stored counter non-zero हो; एक authenticator
        जिसे हम स्थायी रूप से <code>0</code> tag करते हैं, counter-based protection से स्थायी रूप से
        excluded है।
      </p>
      <p><strong>समाधान:</strong> Signature counter हमेशा लिखा जाता है। Clone detection threshold interpretation को handle करता है।</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>निष्कर्ष 6 &mdash; Revoked Passkey फिर से Register हो सकता था <span class="doc-badge medium">Medium</span></h2>
      <p><strong>श्रेणी: प्रमाणीकरण</strong></p>
      <p>
        जब एक credential revoked mark किया गया था (clone detection triggered), registration path
        में कोई check नहीं था जो उसी <code>credential_id</code> को re-register होने से रोके।
        Raw passkey credential और account access के साथ एक adversary revoked credential को
        re-register कर सकता था, उसका compromised history मिटाते हुए।
      </p>
      <p>
        Revocation तभी सार्थक है जब यह permanent हो। यदि इसे उसी credential का उपयोग करके
        re-registration द्वारा overwrite किया जा सकता है, तो clone detection कोई lasting
        protection प्रदान नहीं करता।
      </p>
      <p><strong>समाधान:</strong> यदि existing credential record पर <code>revoked_at</code> non-empty है, तो
      re-registration HTTP 403 के साथ blocked है और एक security log entry लिखी जाती है।</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>निष्कर्ष 7 &mdash; अलग-अलग Error Responses के माध्यम से Account Enumeration <span class="doc-badge medium">Medium</span></h2>
      <p><strong>श्रेणी: सूचना प्रकटीकरण</strong></p>
      <p>
        जब किसी unrecognized email के साथ passkey login का प्रयास किया गया, तो error response body
        अन्य failure cases से अलग form लेता था — एक empty <code>[]</code> data payload बनाम
        <code>{'error': 'passkey_invalid'}</code> body जो अन्य जगह return होता था। API probe करने
        वाला client response body inspect करके &ldquo;इस email का कोई account नहीं&rdquo; को
        &ldquo;यह email exists है लेकिन challenge failed&rdquo; से अलग कर सकता था।
      </p>
      <p>
        इसके अतिरिक्त, raw email address observability log में लिखी गई थी। Log aggregation
        pipelines में कभी raw user email addresses नहीं होनी चाहिए — यदि log system compromised
        होता है, तो हर enumeration attempt एक email address list बन जाती है।
      </p>
      <p><strong>समाधान:</strong> &ldquo;Email not found&rdquo; और &ldquo;no credentials registered&rdquo; दोनों अब
      वही error body return करते हैं। Observability log केवल email का SHA-256 hash record करता है —
      incident correlation के लिए पर्याप्त, address reconstruct करने के लिए अपर्याप्त।</p>
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
      <h2>निष्कर्ष 8 &mdash; Email Delivery Confirm होने से पहले Recovery Key State लिखा गया <span class="doc-badge medium">Medium</span></h2>
      <p><strong>श्रेणी: डेटा अखंडता</strong></p>
      <p>
        Account recovery key generation के दौरान, server ने recovery key email भेजने से
        <em>पहले</em> user record में <code>recovery_key_generated = 1</code> और
        <code>recovery_proof_key</code> लिखा:
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
        यदि email send fail होती, तो database <code>recovery_key_generated = 1</code> दिखाता —
        system मानता है कि एक key issue हुई। User ने इसे कभी receive नहीं किया।
      </p>
      <p>
        इस state में user के लिए कोई regeneration path नहीं है। Account recovery उस account
        के लिए manual intervention तक permanently broken है।
      </p>
      <p><strong>समाधान:</strong> Email delivery पहले confirm होती है। Database state वही reflect करता है जो वास्तव में हुआ।</p>
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
      <h2>निष्कर्ष 9 &mdash; Disabled Registration Path अभी भी Password Fields Collect कर रहा था <span class="doc-badge low">Low</span></h2>
      <p><strong>श्रेणी: Attack Surface</strong></p>
      <p>
        <code>RegistrationController</code> अभी भी POST से <code>password</code> और
        <code>confirm_password</code> पढ़ता था, भले ही password-based registration disabled था।
        PayCal registration exclusively passkey-only है।
      </p>
      <p>
        उन fields collect करना जो कोई purpose serve नहीं करते, harmless नहीं है। User input से
        पढ़ा गया प्रत्येक value एक surface है: इसे log किया जा सकता है, audit किया जा सकता है,
        गलती से अन्य functions को pass किया जा सकता है, या error payloads में include किया जा
        सकता है। Minimal surface principle के लिए आवश्यक है कि हम वह collect न करें जो हम उपयोग नहीं करते।
      </p>
      <p><strong>समाधान:</strong> दोनों fields input collection map से remove किए गए।</p>
    </section>

    <section class="doc-section">
      <h2>निष्कर्ष 10 &mdash; Email Verification 403 Response में User Email <span class="doc-badge low">Low</span></h2>
      <p><strong>श्रेणी: सूचना प्रकटीकरण</strong></p>
      <p>
        <code>EmailVerificationGuard</code> — protected resources तक access grant करने से पहले
        email verification enforce करने वाला middleware — 403 response body में <code>user_email</code>
        include करता था:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        यदि कोई attacker एक valid लेकिन unverified session token obtain करता है (session fixation
        या compromised temporary link के माध्यम से), तो वह 403 response body से account से जुड़ा
        email address जान सकता है — email खुद provide किए बिना। इस error payload में email से
        benefit करने वाली एकमात्र party वह है जिसके पास session token है लेकिन email नहीं है।
      </p>
      <p><strong>समाधान:</strong> Email field को error payload से remove किया गया।</p>
    </section>

    <section class="doc-section">
      <h2>निष्कर्ष 11 &mdash; <code>EmailGarum::verifyNewUserEmail()</code> में Dead Code <span class="doc-badge low">Low</span></h2>
      <p><strong>श्रेणी: Dead Code / Attack Surface</strong></p>
      <p>
        <code>EmailGarum</code> में एक 90-line method <code>verifyNewUserEmail()</code> था, जो
        password-based email change flow handle करता था। जब platform exclusively passkey-based
        authentication पर switch हुआ, तो यह flow replace हो गया। Method को codebase में कहीं
        call नहीं किया जाता था।
      </p>
      <p>
        Dead code neutral नहीं है। यह security review surface में, static analysis में, और file
        पढ़ने वाले किसी के भी cognitive load में space लेता है। यह risk भी present करता है कि
        एक future developer, यह न जानते हुए कि यह intentionally abandoned था, इसे पूरे context
        के बिना एक new flow में wire कर सकता है।
      </p>
      <p><strong>समाधान:</strong> Delete किया गया। Removal से पहले सभी call sites empty confirm किए गए।</p>
    </section>

    <section class="doc-section">
      <h2>सभी निष्कर्षों का सारांश</h2>
      <table class="doc-table" aria-label="सभी निष्कर्षों का सारांश">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">निष्कर्ष</th>
            <th scope="col">गंभीरता</th>
            <th scope="col">श्रेणी</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td>9 call sites पर गैर-परमाणु <code>hset + expire</code></td><td><span class="doc-badge high">High</span></td><td>Redis / परमाणुता</td></tr>
          <tr><td>2</td><td>Key enumeration के लिए <code>del()</code> read replica का उपयोग</td><td><span class="doc-badge high">High</span></td><td>Redis / Logout, CSRF</td></tr>
          <tr><td>3</td><td>Account recovery registration में WebAuthn UV bypass</td><td><span class="doc-badge high">High</span></td><td>प्रमाणीकरण</td></tr>
          <tr><td>4</td><td>Sign count clone detection replay attacks चूक गई</td><td><span class="doc-badge medium">Medium</span></td><td>प्रमाणीकरण</td></tr>
          <tr><td>5</td><td>Authenticator zero return पर sign count persist नहीं किया गया</td><td><span class="doc-badge medium">Medium</span></td><td>प्रमाणीकरण</td></tr>
          <tr><td>6</td><td>Revoked passkey फिर से register हो सकता था</td><td><span class="doc-badge medium">Medium</span></td><td>प्रमाणीकरण</td></tr>
          <tr><td>7</td><td>Error body + raw email in logs के माध्यम से account enumeration</td><td><span class="doc-badge medium">Medium</span></td><td>सूचना प्रकटीकरण</td></tr>
          <tr><td>8</td><td>Email confirmation से पहले recovery key DB state लिखा गया</td><td><span class="doc-badge medium">Medium</span></td><td>डेटा अखंडता</td></tr>
          <tr><td>9</td><td>Disabled registration अभी भी password fields collect कर रहा था</td><td><span class="doc-badge low">Low</span></td><td>Attack Surface</td></tr>
          <tr><td>10</td><td>Email verification 403 response में user email</td><td><span class="doc-badge low">Low</span></td><td>सूचना प्रकटीकरण</td></tr>
          <tr><td>11</td><td>EmailGarum में dead method <code>verifyNewUserEmail()</code></td><td><span class="doc-badge low">Low</span></td><td>Dead Code</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>हमने क्या सही किया</h2>
      <p>पूर्ण तस्वीर के हित में — पहले से मौजूद foundations:</p>
      <ul class="doc-list">
        <li>
          <strong>Passkey-first authentication।</strong> Platform passkey users के लिए password
          fallback के बिना WebAuthn पर चलता है। UV bypass और clone detection problems fundamentally
          sound architecture के भीतर defects थे।
        </li>
        <li>
          <strong>One-use capability tokens।</strong> Admin-level mutations पहले से ही fresh,
          time-limited tokens require करते थे। Atomicity fix ने एक missing protection add करने
          के बजाय existing protection को strengthen किया।
        </li>
        <li>
          <strong>Signed security log।</strong> प्रत्येक security event — इस commit में add किए
          गए नए <code>passkey_revoked_reregistration_blocked</code> events सहित — structured fields
          के साथ एक signed, append-only log में लिखे जाते हैं।
        </li>
        <li>
          <strong>PHPStan Level 9 पर।</strong> सभी 11 modified files को maximum static analysis
          rigor पर validate किया गया था। पूर्ण regression suite बिना regression के pass हुआ।
        </li>
        <li>
          <strong>Clone detection exist करती थी।</strong> Logic present था और partially correct था।
          निष्कर्ष 4 एक boundary condition error था, न कि missing feature।
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>ग्राहक प्रभाव</h2>
      <ul class="doc-list">
        <li><strong>शोषण का कोई प्रमाण नहीं।</strong> सभी निष्कर्ष routine code review के माध्यम से internally identify किए गए। कोई external report, CVE या incident इस disclosure से पहले नहीं था।</li>
        <li><strong>कोई plaintext credential exposure नहीं।</strong> कोई password या recovery key expose नहीं हुई। Rest पर credential data encrypted रहता है। Biometric data authenticator device से कभी नहीं निकलता और PayCal द्वारा कभी transmit या stored नहीं किया जाता।</li>
        <li><strong>Unauthorized account access का कोई प्रमाण नहीं।</strong> Security logs इन vectors के exploitation के अनुरूप कोई anomalous pattern नहीं दिखाते।</li>
        <li><strong>सभी निष्कर्ष disclosure से पहले remediated।</strong> इस article में describe की गई प्रत्येक समस्या को इस page publish होने से पहले fixed, committed और tested किया गया था।</li>
        <li><strong>पूर्ण regression suite validated।</strong> Full PHPUnit suite और PHPStan Level 9 static analysis remediation के बाद cleanly complete।</li>
        <li><strong>Monitoring extended।</strong> Future anomalies पहले uncover करने के लिए passkey revocation enforcement (निष्कर्ष 6) के लिए नए security log events add किए गए।</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>रोकथाम और पुनरावृत्ति नियंत्रण</h2>
      <p>इस audit से permanent policy के रूप में adopt किए गए दो engineering rules:</p>
      <div class="subject-example-cutout" role="note" aria-label="नया engineering rule: hsetex default Redis write pattern के रूप में">
        <h3><code>hsetex</code> default Redis write pattern है</h3>
        <p>
          भविष्य का कोई भी code जिसे TTL के साथ hash लिखना हो, <code>Database::hsetex()</code>
          उपयोग करना होगा। पुराना two-step pattern अब permitted नहीं है। नई occurrences flag
          करने के लिए PHPStan rules लिखी जाएंगी।
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="नया engineering rule: सभी key operations के लिए write instance primacy">
        <h3>सभी key operations के लिए write instance primacy</h3>
        <p>
          कोई भी Redis operation जिसकी correctness अभी-अभी लिखे गए को re-reading पर depend करती
          है, write instance उपयोग करना होगा। Read replicas केवल non-critical, high-read queries के लिए हैं।
        </p>
      </div>
      <p>
        इस specificity level पर self-audits एक ongoing commitment हैं। हम जो find करते हैं उसे
        publish करते रहेंगे। Future reports
        <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a> पर publish होंगे।
      </p>
    </section>

    <section class="doc-section">
      <h2>प्रकटीकरण समयरेखा</h2>
      <table class="doc-table" aria-label="प्रकटीकरण समयरेखा">
        <thead>
          <tr>
            <th scope="col">तारीख</th>
            <th scope="col">घटना</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">12 मई 2026</time></td>
            <td>Routine internal audit session के दौरान निष्कर्ष identify किए गए</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 मई 2026</time></td>
            <td>सभी fixes implement और committed किए गए (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 मई 2026</time></td>
            <td>Full PHPUnit regression suite passed, PHPStan Level 9 clean</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 मई 2026</time></td>
            <td>origin/main पर push किया गया</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 मई 2026</time></td>
            <td>यह transparency article publish किया गया</td>
          </tr>
        </tbody>
      </table>
      <p>
        सभी निष्कर्ष internally identify किए गए। कोई external report, CVE या breach इस disclosure
        से पहले नहीं थी। इस बात का कोई प्रमाण नहीं है कि किसी निष्कर्ष का शोषण किया गया।
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
