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
$pageTitle = 'त्रुटि प्रबंधन और संदेश सामान्यीकरण - [PayCal]';
$pageLabel = 'त्रुटि प्रबंधन और संदेश सामान्यीकरण';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">त्रुटि प्रबंधन और संदेश सामान्यीकरण</span>
  </nav>

  <header class="doc-article-header">
    <h1>त्रुटि प्रबंधन और संदेश सामान्यीकरण</h1>
    <p class="deck">
      कैसे PayCal सभी फ्रंटएंड मॉड्यूल में त्रुटि रिपोर्टिंग को मानकीकृत करता है ताकि
      उपयोगकर्ताओं को संवेदनशील विवरण प्रकट किए बिना सार्थक, सुरक्षित और सुसंगत प्रतिक्रिया मिले।
    </p>
<p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>अवलोकन और उद्देश्य</h2>
      <p>
        जब उपयोगकर्ताओं को त्रुटियाँ मिलती हैं (नेटवर्क विफलता, अनुमति अस्वीकृत, सत्यापन त्रुटियाँ),
        उन्हें स्पष्ट प्रतिक्रिया मिलनी चाहिए जो बताए कि क्या हुआ और इसे कैसे ठीक करें।
        हालाँकि, बैकएंड से कच्चे संदेशों को सामान्यीकृत किया जाना चाहिए:
      </p>
      <ul class="doc-list">
        <li><strong>शोर हटाएं:</strong> अनावश्यक "त्रुटि:" उपसर्ग और व्हाइटस्पेस हटाएं</li>
        <li><strong>रिसाव रोकें:</strong> सुनिश्चित करें कि संवेदनशील कार्यान्वयन विवरण कभी उपयोगकर्ता तक न पहुंचें</li>
        <li><strong>फॉलबैक प्रदान करें:</strong> जब त्रुटियाँ खाली या दुर्भावनापूर्ण हों तो सुरक्षित संदेश दिखाएं</li>
        <li><strong>सुसंगतता सुनिश्चित करें:</strong> सभी 11+ फ्रंटएंड मॉड्यूल पर समान तर्क लागू करें</li>
        <li><strong>डीबगिंग में सुधार करें:</strong> Phantom Wing पर पूर्ण त्रुटि विवरण लॉग करें और सुरक्षित सारांश दिखाएं</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>समस्या: सामान्य बनाम सार्थक त्रुटियाँ</h2>
      <p>
        सामान्यीकरण से पहले, PayCal मॉड्यूल अनुकूलित त्रुटि प्रबंधन का उपयोग करते थे:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ गलत: कच्ची त्रुटि प्रकट करता है, तर्क दोहराता है
PC.showToast(error?.message || 'आयात विफल।');
PW.error(`आयात विफल: ${error.message}`);</code></pre>
      </div>
      <p>इस दृष्टिकोण की समस्याएँ:</p>
      <ul class="doc-list">
        <li>उपयोगकर्ता भ्रमित करने वाले संदेश देखते हैं जैसे "ECONNREFUSED: कनेक्शन अस्वीकृत"</li>
        <li>प्रत्येक मॉड्यूल स्वतंत्र रूप से अपना स्वयं का फॉलबैक तर्क लागू करता है</li>
        <li>कोई सुसंगत व्हाइटस्पेस बंद या उपसर्ग हटाना नहीं</li>
        <li>खाली त्रुटि संदेश UI में "अपरिभाषित" के रूप में प्रदर्शित हो सकते हैं</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>समाधान: मानकीकृत त्रुटि समाधानकर्ता</h2>
      <p>
        सभी PayCal फ्रंटएंड मॉड्यूल अब एक एकीकृत समाधानकर्ता फ़ंक्शन का उपयोग करते हैं
        जो त्रुटि संदेशों को सामान्यीकृत करता है:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ सही: सामान्यीकृत, सुसंगत, सुरक्षित
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // त्रुटि ऑब्जेक्ट से संदेश निकालें
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // "त्रुटि:" उपसर्ग हटाएं और व्हाइटस्पेस ट्रिम करें
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // यदि गैर-खाली तो सामान्यीकृत वापसी करें; अन्यथा सुरक्षित फॉलबैक
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>उपयोग:</strong></p>
      <div class="doc-code-block">
        <pre><code>// सभी मॉड्यूल में कैच ब्लॉक में
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'प्रोफ़ाइल अपडेट नहीं की जा सकी।');
  PC.showToast(message, 'error');   // उपयोगकर्ता सार्थक प्रतिक्रिया देखता है
  PW.error(message);                 // डीबगिंग के लिए लॉग किया गया
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>कार्यान्वयन का दायरा</h2>
      <p>
        अप्रैल 2026 तक, यह मानकीकृत त्रुटि-प्रबंधन पैटर्न लागू किया गया है
        <strong>11 फ्रंटएंड मॉड्यूल</strong> के साथ <strong>~40+ सामान्यीकृत कैच ब्लॉक</strong>:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>प्रमाणीकरण और सेटिंग्स (7 मॉड्यूल)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>कोर और डेटा मॉड्यूल (4 मॉड्यूल)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>उच्च-मूल्य मॉड्यूल (10+ कैच पॉइंट):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/organizations/index.php</code> — संगठन प्रबंधन, पहुंच, ऑडिट ट्रेल्स (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — साइट CRUD, कमाई, अनाथ कार्य पुनरुद्धार (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — दिन प्रविष्टि संचालन, कॉपी/पेस्ट/डिलीट (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>त्रुटि श्रेणियाँ और प्रबंधन पैटर्न</h2>
      <p>साधक को कई त्रुटि श्रेणियों में लागू किया जाता है:</p>
      
      <h3>1. नेटवर्क अनुरोध विफलताएँ</h3>
      <div class="doc-code-block">
        <pre><code>// नेटवर्क मॉड्यूल: HTTP त्रुटियाँ, टाइमआउट, कनेक्शन समस्याएँ
async function deleteResource(ep, id) {
  try {
    // ...fetch लॉजिक...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'नेटवर्क त्रुटि');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. API प्रतिक्रिया प्रबंधन</h3>
      <div class="doc-code-block">
        <pre><code>// बिलिंग/सेटिंग्स: सर्वर ने पेलोड में त्रुटि संदेश वापस किया
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'बिलिंग स्थिति लोड नहीं की जा सकी।');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'बिलिंग स्थिति लोड नहीं की जा सकी।');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. UI संचालन विफलता</h3>
      <div class="doc-code-block">
        <pre><code>// कैलेंडर/संगठन: उपयोगकर्ता-शुरुआत किए गए कार्य (पेस्ट, डिलीट, अपडेट)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('सफलता!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'कार्य विफल। फिर से प्रयास करें।');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. अतुल्यकालिक आरंभीकरण</h3>
      <div class="doc-code-block">
        <pre><code>// कोर मॉड्यूल: स्टार्टअप या आश्रित आरंभीकरण विफलता
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'नेविगेशन init विफल');
  PW.warn(resolved);  // लॉग किया गया लेकिन पृष्ठ को ब्लॉक नहीं करता
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>सुरक्षा विचार</h2>
      <p>
        त्रुटि संदेश सामान्यीकरण उपयोगकर्ता गोपनीयता और प्रणाली अखंडता की रक्षा करता है:
      </p>
      <ul class="doc-list">
        <li>
          <strong>कोई डेटाबेस विवरण नहीं:</strong> बैकएंड त्रुटियाँ जैसे "UNIQUE constraint failed on email"
          API सीमा पर अवरोधित होती हैं
        </li>
        <li>
          <strong>कोई फ़ाइल पथ नहीं:</strong> फ़ाइल पथ या प्रक्रिया विवरण प्रकट करने वाली प्रणाली त्रुटियाँ हटाई जाती हैं
        </li>
        <li>
          <strong>कोई प्रमाणीकरण रिसाव नहीं:</strong> प्रमाणीकरण विफलता के लिए प्रतिक्रियाएं कभी भी
          प्रकट नहीं करती कि किसी खाते का अस्तित्व है (केवल सामान्य सुरक्षित संदेश)
        </li>
        <li>
          <strong>कोई CORS/नेटवर्क विवरण नहीं:</strong> परिवहन-स्तर की त्रुटियों को
          सामान्य "कनेक्शन त्रुटि" संदेशों में सामान्यीकृत किया जाता है
        </li>
        <li>
          <strong>सुरक्षित फॉलबैक:</strong> सभी कैचर के पास स्पष्ट फॉलबैक संदेश हैं;
          कभी भी "अनिर्धारित" या "null" प्रदर्शित न करें
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>उपयोगकर्ता अनुभव लाभ</h2>
      <p>
        मानकीकृत त्रुटि संदेश उपयोगकर्ता अनुभव में काफी सुधार करते हैं:
      </p>
      <ul class="doc-list">
        <li>
          <strong>स्पष्ट प्रतिक्रिया:</strong> उपयोगकर्ता जानते हैं कि क्या विफल हुआ
          (उदा. "पास की पहचान नहीं की गई" बनाम सामान्य "साइन इन विफल")
        </li>
        <li>
          <strong>कार्यवाही योग्य अगले कदम:</strong> जहाँ संभव हो, संदेश समाधान सुझाते हैं
          ("फिर से प्रयास करें", "अपना कनेक्शन जाँचें", "समर्थन से संपर्क करें")
        </li>
        <li>
          <strong>ऐप में सुसंगतता:</strong> समान त्रुटि प्रकार हर जगह समान रूप से प्रदर्शित होती हैं,
          उपयोगकर्ता भ्रम को कम करता है
        </li>
        <li>
          <strong>सुलभ त्रुटि अवस्थाएँ:</strong> स्क्रीन पाठक सामान्यीकृत संदेशों की घोषणा करते हैं;
          लॉगिंग समर्थन दलों को पूर्ण संदर्भ प्रदान करता है
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>डीबगिंग और समर्थन वर्कफ़्लो</h2>
      <p>
        त्रुटि सामान्यीकरण डीबगिंग क्षमता का त्याग <strong>नहीं</strong> करता है।
        पूर्ण त्रुटि विवरण Phantom Wing में प्रवाहित होते हैं:
      </p>
      <div class="doc-code-block">
        <pre><code>// उपयोगकर्ता स्वच्छ UI संदेश देखता है
PC.showToast(resolveThrownMessage(error, 'अपलोड विफल।'), 'error');

// समर्थन दल Phantom Wing लॉग में पूर्ण विवरण देखता है
PW.error('अपलोड विफल', {
  userMessage: resolveThrownMessage(error, 'अपलोड विफल।'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>परीक्षण और गुणवत्ता आश्वासन</h2>
      <p>
        सभी त्रुटि-प्रबंधन परिवर्तनों को तैनाती से पहले सत्यापित किया जाता है:
      </p>
      <ul class="doc-list">
        <li><strong>सिंटैक्स सत्यापन:</strong> <code>php -l</code> और <code>node --check</code> शुद्धता सत्यापित करें</li>
        <li><strong>प्रकार सुरक्षा:</strong> संपादक डायग्नोस्टिक्स कोई प्रकार प्रतिगमन की पुष्टि करें</li>
        <li><strong>एकीकरण परीक्षण:</strong> नकली त्रुटि ऑब्जेक्ट के साथ कैच ब्लॉक परीक्षण</li>
        <li><strong>Phantom Wing लॉगिंग:</strong> डीबग लॉग में त्रुटि संदेश सत्यापित करें</li>
        <li><strong>सुलभता ऑडिट:</strong> स्पष्टता के लिए स्क्रीन रीडर घोषणाएँ परीक्षण करें</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>रखरखाव और भविष्य विस्तार</h2>
      <p>
        यह पैटर्न दीर्घकालिक रखरखाव के लिए डिज़ाइन किया गया है:
      </p>
      <ul class="doc-list">
        <li>
          <strong>स्थानीयकरण के लिए तैयार:</strong> त्रुटि संदेशों को i18n के माध्यम से
          समाधानकर्ता तर्क को संशोधित किए बिना कनेक्ट किया जा सकता है
        </li>
        <li>
          <strong>विस्तारणीय:</strong> समाधानकर्ता को त्रुटि कोड, पुनः प्रयास तर्क,
          या विशेष संदेश लुकअप संभालने के लिए बढ़ाया जा सकता है
        </li>
        <li>
          <strong>दस्तावेज़:</strong> प्रत्येक मॉड्यूल में इनलाइन टिप्पणियाँ शामिल हैं जो
          त्रुटि परिदृश्य और फॉलबैक रणनीतियों की व्याख्या करती हैं
        </li>
        <li>
          <strong>Git इतिहास:</strong> विस्तृत कमिट संदेशों और फ़ाइल-स्तरीय diffs के साथ सभी परिवर्तनों को ट्रैक किया गया
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>सारांश: PayCal त्रुटि-प्रबंधन मानक</h2>
      <p>
        PayCal का मानकीकृत त्रुटि-संदेश सामान्यीकरण सुनिश्चित करता है कि:
      </p>
      <ol class="doc-list">
        <li>उपयोगकर्ताओं को स्पष्ट, कार्यवाही योग्य त्रुटि प्रतिक्रिया मिले</li>
        <li>संवेदनशील प्रणाली विवरण कभी फ्रंटएंड में न पहुँचें</li>
        <li>संदेश प्रबंधन सभी 11+ फ्रंटएंड मॉड्यूल में सुसंगत हो</li>
        <li>डीबगिंग और समर्थन दल Phantom Wing के माध्यम से पूर्ण त्रुटि संदर्भ बनाए रखें</li>
        <li>कोड रखरखाव योग्य, परीक्षण योग्य और accessible हो</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        सुरक्षा, स्पष्टता और सुसंगतता के प्रति यह प्रतिबद्धता PayCal की उपयोगकर्ता विश्वास
        और पारदर्शी जानकारी साझाकरण के लिए समर्पण को दर्शाती है।
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
