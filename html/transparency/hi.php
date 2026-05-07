<?php
/**
 * Public Transparency Hub — हिन्दी
 *
 * PURPOSE: High-level philosophy and links to detailed transparency pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_link.php';

if (!function_exists('transparency_href')) {
  function transparency_href(string $path): string
  {
    return $path;
  }
}

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_HOME',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$readMoreLabel = 'और पढ़ें';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'पारदर्शिता केंद्र - [PayCal]';
$pageLabel = 'पारदर्शिता केंद्र';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">पारदर्शिता केंद्र</span>
    </nav>

    <header class="doc-article-header">
      <h1>पारदर्शिता केंद्र</h1>
      <p class="deck">हम PayCal की कार्यप्रणाली प्रकाशित करते हैं ताकि उपयोगकर्ता निर्णयों की जांच कर सकें — केवल वक्तव्यों पर भरोसा नहीं करना पड़े।</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>प्लेटफ़ॉर्म दर्शन</h2>
        <p>PayCal निरीक्षण योग्य संचालन पर बनाया गया है: फ़ॉर्मूले प्रलेखित हैं, टेलीमेट्री सीमाएं स्पष्ट हैं, और डेटा प्रतिधारण डिफ़ॉल्ट रूप से सीमित है।</p>
        <p>हमारा सिद्धांत सरल है: यदि कोई सिस्टम वेतन या गोपनीयता को प्रभावित करता है, तो उपयोगकर्ताओं को यह समझने में सक्षम होना चाहिए कि यह कैसे काम करता है और इसे कैसे नियंत्रित किया जाता है।</p>
        <p>सदस्यता बिलिंग Stripe द्वारा संसाधित की जाती है। Stripe समर्थन <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a> पर उपलब्ध है।</p>
        <p>उत्पाद को आकार देने वाले हाल के अपडेट — बिलिंग और प्रोफ़ाइल गवर्नेंस प्रवाह सहित — हमारे framework/backend और परीक्षण गवर्नेंस पृष्ठों पर ट्रैक किए जाते हैं।</p>
      </section>

      <div class="doc-panel-grid doc-panel-grid--responsive-3" aria-label="पारदर्शिता विवरण पैनल">
        <section class="doc-section">
          <h2>सुरक्षा ऑडिट स्थिति</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>यह पृष्ठ वर्तमान ऑडिट स्थिति, बंद दायरा, साक्ष्य संदर्भ और रिलीज़-अवरोधक प्रतिबद्धताएं प्रकाशित करता है जो सुरक्षा स्थिति की रक्षा करती हैं।</p>
          <ul class="doc-fact-list">
            <li>वर्तमान चक्र की स्थिति सत्यापन तिथि और समीक्षा आवृत्ति के साथ प्रकाशित की जाती है।</li>
            <li>कवरेज में runtime जीवनचक्र नियंत्रण, टेलीमेट्री अलगाव, सहसंबंध गवर्नेंस और विशेषाधिकार प्राप्त भूमिकाओं की सुदृढ़ता शामिल है।</li>
            <li>सत्यापन स्नैपशॉट में Playwright, JS, PHPStan स्तर 9 और बैकएंड परीक्षण परिणाम शामिल हैं।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>प्लेटफ़ॉर्म मेट्रिक्स और गोपनीयता</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>मेट्रिक्स पृष्ठ विश्वसनीयता और क्षमता योजना के लिए एकत्र की गई परिचालन टेलीमेट्री की व्याख्या करता है।</p>
          <ul class="doc-fact-list">
            <li>टेलीमेट्री कुंजियाँ और उदाहरण प्रकाशित किए जाते हैं ताकि दावे सत्यापन योग्य हों।</li>
            <li>संग्रह का दायरा केवल एकत्रित है, सख्त सीमाओं के साथ और कुंजियों में कोई व्यक्तिगत पहचानकर्ता नहीं।</li>
            <li>प्रतिधारण एक परिभाषित जीवनचक्र का पालन करता है: कच्चे डेटा, समुच्चय और स्वचालित पर्ज।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>अभिगम्यता और WCAG अनुपालन</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>हम WCAG 2.1 स्तर AA को अपने कार्यशील अभिगम्यता मानक के रूप में उपयोग करते हैं और हाल के अभिगम्यता कार्य को सरल भाषा में प्रकाशित करते हैं।</p>
          <ul class="doc-fact-list">
            <li>मुख्य नेविगेशन कीबोर्ड उपयोग, स्किप लिंक और मुख्य गंतव्यों के लिए एकल-कुंजी शॉर्टकट का समर्थन करता है।</li>
            <li>शॉर्टकट हैंडलिंग सुरक्षित है और संपादन योग्य फ़ील्ड में टाइप करते समय या डायलॉग खुले होने पर सक्रिय नहीं होती।</li>
            <li>हाल की प्रतिगमन कवरेज शीर्षक, रिफ्लो/टेक्स्ट स्पेसिंग, नेविगेशन पथ और अभिगम्यता फ़ीडबैक की जांच करती है।</li>
            <li>मुख्य सार्वजनिक पृष्ठों पर सख्त रूट-स्तरीय कंट्रास्ट ब्लॉकर्स को ठीक किया गया है; थीम-व्यापी कंट्रास्ट कार्य जारी है।</li>
            <li>उपयोगकर्ता अभिगम्यता पृष्ठ से एक अभिगम्यता रिपोर्ट शुरू कर सकते हैं और इसे सुरक्षित संपर्क प्रवाह के माध्यम से जारी रख सकते हैं।</li>
            <li>अभिगम्यता पारदर्शिता पृष्ठ अब अंतिम-सत्यापित तिथि, सत्यापन दायरा, ज्ञात सीमाएं और अगली समीक्षा की नियत तारीख प्रकाशित करता है।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>सत्यापन और गवर्नेंस</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>यह पृष्ठ दस्तावेज़ करता है कि PayCal परीक्षण, हुक, runtime सीमाओं और सुरक्षा नियंत्रणों के माध्यम से नीतियों को कैसे लागू करता है।</p>
          <ul class="doc-fact-list">
            <li>Pre-commit और pre-push हुक PHPStan स्तर 9 लागू करते हैं और बेसलाइन बाईपास को अस्वीकार करते हैं।</li>
            <li>CI यूनिट, एकीकरण, अनुबंध, यादृच्छिक-क्रम और कवरेज जॉब में स्तरीय सत्यापन चलाता है।</li>
            <li>Runtime नियंत्रण संवेदनशील प्रवाहों के लिए दर सीमाएं, TTL विंडो और दुरुपयोग-प्रतिक्रिया ब्लॉक लागू करते हैं।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>नेटवर्क क्षमताएं</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>यह लेख ब्राउज़र और नेटवर्क व्यवहार को सुरक्षित करने के लिए उपयोग किए जाने वाले परिवहन प्रोटोकॉल और प्रतिक्रिया हेडर नियंत्रण प्रकाशित करता है।</p>
          <ul class="doc-fact-list">
            <li>HTTPS प्रवर्तन, HSTS प्रीलोड और HTTP/3 (QUIC) विज्ञापन का दस्तावेज़ीकरण।</li>
            <li>CSP, COOP, COEP, CORP और ब्राउज़र हार्डनिंग हेडर सहित वर्तमान सुरक्षा हेडर बेसलाइन सूचीबद्ध करता है।</li>
            <li>आधुनिक क्लाइंट में प्रोटोकॉल वार्ता और फ़ॉलबैक व्यवहार की व्याख्या करता है।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>परीक्षण गवर्नेंस</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>यह लेख दस्तावेज़ करता है कि हम बैकएंड, फ्रंटएंड और अभिगम्यता सत्यापन कैसे चलाते हैं और कौन से गेट रिलीज़ ब्लॉकर के रूप में माने जाते हैं।</p>
          <ul class="doc-fact-list">
            <li>सक्रिय PHPUnit सूट सूची और श्रेणी विभाजन दिखाता है।</li>
            <li><code>/mis</code> स्वीप में उपयोग किए जाने वाले रिलीज़-अवरोधक सत्यापन कमांड का दस्तावेज़ीकरण।</li>
            <li>बताता है कि परीक्षण साक्ष्य changelog और source-of-truth नोट्स में कैसे समन्वयित किया जाता है।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>निर्भरता और CI/CD गवर्नेंस</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>यह लेख प्रकाशित करता है कि npm निर्भरताओं को कैसे नियंत्रित किया जाता है और रिलीज़ से पहले CI गेट कैसे लागू किए जाते हैं।</p>
          <ul class="doc-fact-list">
            <li>lockfile-first npm नीति और <code>npm ci</code> ऑटोमेशन आवश्यकताओं का दस्तावेज़ीकरण।</li>
            <li>JavaScript गुणवत्ता गेट और बैकएंड पाइपलाइन चरणों को वर्कफ़्लो नियंत्रणों से मैप करता है।</li>
            <li>ज्ञात दस्तावेज़ीकरण सीमाएं और नियोजित गवर्नेंस सुधारों की सूची देता है।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Framework और बैकएंड परिवर्तन लॉग</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>यह पृष्ठ बैकएंड आर्किटेक्चर और framework-स्तरीय परिवर्तनों को सार्वजनिक व्याख्याओं के साथ ट्रैक करता है — क्या बदला और क्यों।</p>
          <ul class="doc-fact-list">
            <li>सेवा/नियंत्रक परिवर्तनों का सारांश जो व्यवहार को भौतिक रूप से प्रभावित करते हैं।</li>
            <li>रिलीज़ परिवर्तनों को सुरक्षा और गवर्नेंस नियंत्रणों से मैप करता है।</li>
            <li>विस्तृत changelog और ऑडिट आर्टिफैक्ट के संदर्भ शामिल हैं।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>उत्पाद और बिलिंग परिवर्तन</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>प्रमुख खाता, बिलिंग और प्रोफ़ाइल-फ़्लो अपडेट बैकएंड और परीक्षण गवर्नेंस के साथ समझाए जाते हैं ताकि उपयोगकर्ता UX और व्यवहार दोनों परिवर्तनों का ऑडिट कर सकें।</p>
          <ul class="doc-fact-list">
            <li>बिलिंग-स्थिति हैंडलिंग और सदस्यता स्थिति अनुबंध परिवर्तनों को ट्रैक करता है।</li>
            <li>विनाशकारी-क्रिया सुरक्षा उपायों को कैप्चर करता है जैसे खाता हटाने की स्पष्ट पुष्टि वाक्यांश।</li>
            <li>उत्पाद-सामना करने वाले अपडेट को सत्यापन और रिलीज़ गवर्नेंस साक्ष्य से लिंक करता है।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>कर पद्धति</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>कर पृष्ठ अनुमानों के लिए उपयोग किए गए हमारे CRA-संरेखित फ़ॉर्मूले, थ्रेशोल्ड और उदाहरण दस्तावेज़ करता है।</p>
          <ul class="doc-fact-list">
            <li>CPP, OAS, EI, संघीय/प्रांतीय कर और शुद्ध-वेतन फ़ॉर्मूले कार्य किए गए उदाहरणों के साथ प्रलेखित हैं।</li>
            <li>वर्तमान कर-वर्ष थ्रेशोल्ड और दरें प्रकाशित हैं और CRA संदर्भों से जुड़ी हैं।</li>
            <li>गणना गुणवत्ता एक स्वचालित परीक्षण सूट और वार्षिक दर अपडेट के साथ मान्य है।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>ईमेल आर्किटेक्चर</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>ईमेल पृष्ठ बताता है कि PayCal कौन से लेनदेन संबंधी ईमेल भेजता है, टेम्पलेट कैसे रेंडर किए जाते हैं और वितरण विश्वसनीयता कैसे सत्यापित की जाती है।</p>
          <ul class="doc-fact-list">
            <li>फ़्लो-विशिष्ट टेम्पलेट परिवार सत्यापन, पुनर्प्राप्ति, ईमेल-बदलाव और संपर्क समर्थन पथों में प्रलेखित हैं।</li>
            <li>वितरण जिम्मेदारियां EmailGarum ऑर्केस्ट्रेशन और EmailTransport SMTP प्रोटोकॉल हैंडलिंग के बीच अलग हैं।</li>
            <li>टेम्पलेट स्वीप और DKIM/DMARC स्वास्थ्य सत्यापन के लिए ऑप्ट-इन लाइव परीक्षण प्रलेखित हैं।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Earnings लोड परीक्षण</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>यह लेख <code>/earnings/</code> पर eager rendering बनाम lazy section loading के लिए पुनः उत्पादन योग्य A/B बेंचमार्क परिणाम प्रकाशित करता है।</p>
          <ul class="doc-fact-list">
            <li>वास्तविक और सिंथेटिक 2025/2026 डेटासेट के लिए 10-रन मैट्रिक्स शामिल है।</li>
            <li>DOMContentLoaded, सेक्शन-तैयार समय और API-कॉल ट्रेड-ऑफ़ की रिपोर्ट करता है।</li>
            <li>सार्वजनिक समीक्षा के लिए परीक्षण विधि और व्याख्या प्रलेखित है।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>सुपरहीरो मानचित्र</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>सुपरहीरो पृष्ठ PayCal के थीम वाले क्रॉस-कटिंग घटकों और प्रत्येक के द्वारा हल की जाने वाली विशिष्ट परिचालन समस्या का दस्तावेज़ करता है।</p>
          <ul class="doc-fact-list">
            <li>ShadowTalon, Guardian, Phantom Wing, Lens और EmailGarum शामिल हैं।</li>
            <li>बताता है कि प्रत्येक घटक कहां उपयोग किया जाता है और यह किस जोखिम सीमा की रक्षा करता है।</li>
            <li>सत्यापन एंकर प्रदान करता है ताकि कार्यान्वयन दावों को कोड और परीक्षणों में सीधे जांचा जा सके।</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
