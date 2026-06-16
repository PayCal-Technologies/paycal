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
    <span class="current">ऑप्ट-इन डायग्नोस्टिक्स &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      PayCal में एक वैकल्पिक डायग्नोस्टिक्स परत शामिल है जिसे आप नियंत्रित करते हैं। यहाँ
      बताया गया है कि यह वास्तव में क्या संग्रहीत करती है, क्या आपके डिवाइस पर रहता है,
      और इसका उपयोग कैसे होता है।
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>अवलोकन</h2>
      <p>
        PayCal में एक अंतर्निहित डायग्नोस्टिक्स परत है जिसे <strong>Phantom Wing</strong> कहा जाता है।
        डिफ़ॉल्ट रूप से यह लगभग पूरी तरह से चुप रहती है — यह केवल गंभीर, अनहैंडल्ड त्रुटियों को
        कैप्चर करती है और आपकी स्पष्ट ऑप्ट-इन के बिना कुछ भी कभी नहीं भेजती।
      </p>
      <p>
        यदि आपको कोई समस्या आती है और आप सपोर्ट के साथ अधिक संदर्भ साझा करना चाहते हैं,
        तो आप <a href="/settings/diagnostics/">सेटिंग्स → डीबगिंग (वैकल्पिक)</a> में
        अतिरिक्त डायग्नोस्टिक्स सक्षम कर सकते हैं।
        प्रत्येक सेटिंग स्वतंत्र है; आप केवल वही सक्षम कर सकते हैं जो प्रासंगिक हो।
        सभी तीन डिफ़ॉल्ट रूप से <strong>बंद</strong> हैं।
      </p>
    </section>

    <section class="doc-section">
      <h2>तीन ऑप्ट-इन नियंत्रण</h2>
      <p>
        प्रत्येक नियंत्रण आपके सेटिंग्स पेज के निचले भाग में <strong>डीबगिंग (वैकल्पिक)</strong>
        पैनल में होता है। ये केवल समस्या निवारण के लिए डिज़ाइन किए गए हैं — इन्हें चालू करने से
        पेज इंटरैक्शन थोड़ा धीमा हो सकता है क्योंकि ब्राउज़र में अतिरिक्त कार्य होता है।
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>सेटिंग</th>
            <th>यह क्या सक्षम करती है</th>
            <th>कौन देखता है</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>कंसोल संदेश</strong></td>
            <td>
              आपके ब्राउज़र डेवलपर कंसोल में चेतावनियाँ, सूचनात्मक लॉग और प्रदर्शन मार्कर
              उत्सर्जित करता है। स्व-निदान के लिए उपयोगी — DevTools खोलें और
              <code>[PayCal]</code> या emoji मार्करों से प्रारंभ होने वाले संदेश देखें।
            </td>
            <td>केवल आप (आपका ब्राउज़र कंसोल, कभी प्रसारित नहीं)</td>
          </tr>
          <tr>
            <td><strong>विस्तृत डायग्नोस्टिक्स</strong></td>
            <td>
              चरण-दर-चरण आंतरिक इवेंट लॉगिंग सक्षम करता है। Phantom Wing संचालन के पूर्ण
              जीवनचक्र (कैलेंडर लोड, फॉर्म सबमिशन, सेशन इवेंट) को एक इन-मेमोरी लॉग में
              कैप्चर करता है जो किसी भी सपोर्ट रिपोर्ट में शामिल होता है जिसे आप साझा करना
              चुनते हैं।
            </td>
            <td>केवल आप, जब तक आप सपोर्ट रिपोर्ट साझा न करें</td>
          </tr>
          <tr>
            <td><strong>नेटवर्क इनसाइट्स</strong></td>
            <td>
              API अनुरोध टाइमिंग लॉग करता है — प्रत्येक सर्वर राउंड-ट्रिप में कितना समय
              लगता है, प्रतिक्रिया आकार, और क्या बैचिंग या कैशिंग लागू की गई थी। विशिष्ट
              संचालनों पर धीमेपन का निदान करने में मदद करता है।
            </td>
            <td>केवल आप (आपका ब्राउज़र कंसोल, कभी प्रसारित नहीं)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Phantom Wing डिफ़ॉल्ट रूप से क्या करता है</h2>
      <p>
        सभी तीन नियंत्रण बंद होने पर भी, Phantom Wing एक हल्का बेसलाइन मॉनीटर चलाता है
        जो केवल गंभीर विफलताओं को कैप्चर करता है:
      </p>
      <ul class="doc-list">
        <li>अनकैच्ड JavaScript अपवाद (<code>window.onerror</code>)</li>
        <li>अनहैंडल्ड प्रॉमिस रिजेक्शन</li>
        <li>Fetch कॉल जो नेटवर्क त्रुटि के साथ विफल हों (HTTP त्रुटियाँ नहीं — वे प्रति-फीचर हैंडल की जाती हैं)</li>
      </ul>
      <p>
        यह बेसलाइन डेटा पूरी तरह मेमोरी में रहता है और कहीं भी प्रसारित नहीं होता।
        यह पेज लोड पर ब्राउज़र कंसोल में एक सेकंड के सारांश में प्रदर्शित होता है
        ताकि आप जल्दी से देख सकें कि कुछ गलत हुआ या नहीं, फिर हटा दिया जाता है।
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
      <h2>Phantom Wing &amp; टेलीमेट्री</h2>
      <p>
        Phantom Wing में एक हल्का टेलीमेट्री चैनल है जिसका उपयोग समग्र रूप से फीचर
        विश्वसनीयता मापने के लिए किया जाता है — उदाहरण के लिए, यह पता लगाना कि कोई
        विशेष संचालन पूरे प्लेटफॉर्म पर असामान्य दर से विफल हो रहा है।
      </p>
      <h3>टेलीमेट्री क्या भेजती है</h3>
      <ul class="doc-list">
        <li>प्रति घंटे बकेट किए गए अनामीकृत इवेंट काउंट (जैसे <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>त्रुटि श्रेणी और प्रकार — कभी भी पूर्ण त्रुटि संदेश या स्टैक ट्रेस नहीं</li>
        <li>कोई उपयोगकर्ता पहचानकर्ता नहीं, कोई सेशन टोकन नहीं, कोई IP पता नहीं</li>
      </ul>
      <h3>टेलीमेट्री कभी क्या नहीं भेजती</h3>
      <ul class="doc-list">
        <li>आपका नाम, ईमेल या कोई भी खाता विवरण</li>
        <li>आय, वेतन अवधि या वित्तीय डेटा</li>
        <li>पूर्ण त्रुटि संदेश या स्टैक ट्रेस</li>
        <li>URL पथ या क्वेरी स्ट्रिंग</li>
        <li>कीस्ट्रोक या फॉर्म फ़ील्ड मान</li>
      </ul>
      <h3>रेट सीमित करना &amp; बैक-ऑफ</h3>
      <p>
        टेलीमेट्री सबमिशन सर्वर-साइड पर प्रति उपयोगकर्ता प्रति मिनट रेट-सीमित हैं। यदि
        आपका क्लाइंट सीमा से अधिक हो जाता है, तो सर्वर चुपचाप स्वीकार करता है और अतिरिक्त
        को हटा देता है — कुछ भी संग्रहीत नहीं होता। क्लाइंट एक्सपोनेंशियल बैक-ऑफ भी लागू
        करता है: दो लगातार सर्वर-साइड विफलताओं के बाद यह स्वचालित रूप से दस मिनट के लिए
        टेलीमेट्री सबमिशन अक्षम कर देता है।
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
      <h2>डेटा रिडेक्शन</h2>
      <p>
        किसी भी मान को मेमोरी में संग्रहीत करने या टेलीमेट्री के माध्यम से प्रसारित करने से
        पहले, Phantom Wing एक स्वचालित रिडेक्शन पास लागू करता है। ज्ञात संवेदनशील पैटर्न
        से मेल खाने वाले मानों को <code>[REDACTED]</code> से बदला जाता है:
      </p>
      <ul class="doc-list">
        <li>ईमेल पते</li>
        <li>Bearer टोकन और प्राधिकरण हेडर मान</li>
        <li>CSRF टोकन</li>
        <li>ऐसी स्ट्रिंग जो क्रिप्टोग्राफिक कुंजियों या न्यूनतम लंबाई से ऊपर के base64-एन्कोडेड ब्लॉब जैसी लगती हैं</li>
      </ul>
      <p>
        रिडेक्शन इंटरसेप्ट किए गए कंसोल मेथड में पारित सभी तर्कों और क्यूइंग से पहले सभी
        टेलीमेट्री फ़ील्ड मानों पर काम करता है। इसे डायग्नोस्टिक सेटिंग्स सक्षम करके बायपास
        नहीं किया जा सकता।
      </p>
    </section>

    <section class="doc-section">
      <h2>स्कोप गार्ड: जिन पेजों पर डायग्नोस्टिक्स दबाई जाती हैं</h2>
      <p>
        प्रमाणीकरण पेजों (<code>/auth/</code>) पर टेलीमेट्री सबमिशन पूरी तरह से दबाई जाती है।
        इसका मतलब है कि भले ही नेटवर्क इनसाइट्स चालू हो, साइन-इन, साइन-अप या रिकवरी फ्लो
        पर रहते हुए कोई टेलीमेट्री नहीं भेजी जाती। यह एक डेप्थ-इन-डिफेंस उपाय है जो
        डायग्नोस्टिक चैनलों में क्रेडेंशियल-आसन्न डेटा की किसी भी संभावना को रोकता है।
      </p>
    </section>

    <section class="doc-section">
      <h2>आपका नियंत्रण</h2>
      <p>
        तीनों डायग्नोस्टिक सेटिंग्स खाता प्राथमिकताओं के रूप में संग्रहीत की जाती हैं,
        ब्राउज़र कुकीज़ के रूप में नहीं। वे आपके खाते के साथ सभी डिवाइस और सेशन में
        चलती हैं और हर खाते के लिए — नए खातों सहित — डिफ़ॉल्ट रूप से <strong>बंद</strong> हैं।
        आप उन्हें किसी भी समय <a href="/settings/diagnostics/">सेटिंग्स → डीबगिंग (वैकल्पिक)</a> में
        बदल सकते हैं।
      </p>
      <p>
        किसी सेटिंग को बंद करना अगले पेज लोड पर तुरंत प्रभावी होता है।
        सेशन के बीच कोई डायग्नोस्टिक डेटा नहीं रखा जाता: जब आप कहीं और नेविगेट करते हैं
        या टैब बंद करते हैं तो Phantom Wing का इन-मेमोरी लॉग साफ़ हो जाता है।
      </p>
    </section>

    <section class="doc-section">
      <h2>सारांश</h2>
      <ol class="doc-list">
        <li>सभी तीन डीबग नियंत्रण डिफ़ॉल्ट रूप से <strong>बंद</strong> हैं और आपके द्वारा स्पष्ट रूप से सक्षम किए जाने चाहिए</li>
        <li>कंसोल संदेश और नेटवर्क इनसाइट्स कभी आपके डिवाइस से नहीं निकलते</li>
        <li>विस्तृत डायग्नोस्टिक्स मेमोरी में रहते हैं और तभी साझा किए जाते हैं जब आप सपोर्ट रिपोर्ट साझा करना चुनते हैं</li>
        <li>टेलीमेट्री केवल अनामीकृत, समग्र इवेंट काउंट भेजती है — शून्य व्यक्तिगत डेटा</li>
        <li>डायग्नोस्टिक सेटिंग्स की परवाह किए बिना, संग्रहण या प्रसारण से पहले सभी मान रिडेक्ट किए जाते हैं</li>
        <li>टेलीमेट्री सभी प्रमाणीकरण पेजों पर पूरी तरह दबाई जाती है</li>
        <li>रेट सीमित करना और स्वचालित क्लाइंट बैक-ऑफ किसी भी आकस्मिक ओवर-रिपोर्टिंग को रोकते हैं</li>
      </ol>
      <p class="doc-section-footer-note">
        Phantom Wing को इस तरह इंजीनियर किया गया है कि आप सभी डायग्नोस्टिक्स को
        अनिश्चित काल के लिए बंद रख सकते हैं। ऑप्ट-इन नियंत्रण इसलिए मौजूद हैं ताकि
        कुछ गलत होने पर आपको और सपोर्ट टीम को एक साझा भाषा मिल सके — डिफ़ॉल्ट रूप से
        डेटा संग्रह करने के लिए नहीं।
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
