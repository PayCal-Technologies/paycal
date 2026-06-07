<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'PayCal mein SOC 2 Anupalana - [PayCal]';
$pageLabel = 'PayCal mein SOC 2 Anupalana';

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="ब्रेडक्रम्ब">
    <a href="<?php echo transparency_href('/transparency/'); ?>">पारदर्शिता केंद्र</a>
    <span class="separator">/</span>
    <span class="current">PayCal में SOC 2 अनुपालन</span>
  </nav>

  <header class="doc-article-header">
    <h1>PayCal की SOC 2 तैयारी और सुरक्षा मॉडल</h1>
    <p class="deck">PayCal किस प्रकार SOC 2 नियंत्रणों को प्रवर्तित सिस्टम व्यवहार और निरंतर उत्पन्न साक्ष्यों से जोड़ता है, इसका एक तकनीकी दृष्टिकोण।</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. अवलोकन</h2>
      <p>PayCal एक SOC 2-संरेखित सुरक्षा कार्यक्रम संचालित करता है जो केवल नीति-आधारित दावों के बजाय सत्यापन योग्य प्रवर्तन और ट्रेस करने योग्य साक्ष्यों पर केंद्रित है।</p>
      <ul class="doc-fact-list">
        <li><strong>दायरे में नियंत्रण:</strong> CC1-CC9</li>
        <li><strong>वर्तमान बंडल में आर्टिफैक्ट:</strong> 37</li>
        <li><strong>नियंत्रण-से-आर्टिफैक्ट मैपिंग:</strong> 26</li>
        <li><strong>साक्ष्य ताजगी विंडो:</strong> 35 दिन</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. नियंत्रण कवरेज (CC1-CC9)</h2>
      <p>दायरे में सभी SOC 2 Common Criteria नियंत्रण (CC1 से CC9) मासिक बंडल में बनाए गए साक्ष्यों से मैप किए गए हैं।</p>
      <p>यह मैपिंग नियंत्रण उद्देश्य से समीक्षा के लिए उपयोग किए जाने वाले ठोस आर्टिफैक्ट तक प्रत्यक्ष ट्रेसेबिलिटी का समर्थन करती है।</p>
    </section>

    <section class="doc-section">
      <h2>3. नियंत्रण कैसे लागू किए जाते हैं</h2>
      <p>PayCal प्रवर्तन को एक सिस्टम गुण मानता है। नियंत्रण केवल दस्तावेज़ीकृत नहीं, बल्कि प्रोग्रामेटिक रूप से लागू किए जाते हैं।</p>
      <ul class="doc-fact-list">
        <li><strong>प्रमाणीकरण:</strong> फ़िशिंग प्रतिरोध को मजबूत करने के लिए पासकी-सक्षम प्रमाणीकरण प्रवाह।</li>
        <li><strong>रनटाइम अखंडता:</strong> परिचालन अवस्था प्रबंधन के साथ रनटाइम अखंडता निगरानी।</li>
        <li><strong>आउटपुट हार्डनिंग:</strong> संवेदनशील DOM/आउटपुट पथों के लिए Guardian सैनिटाइज़ेशन नियंत्रण।</li>
        <li><strong>गुणवत्ता गेट:</strong> बंडल साक्ष्य स्वीकार किए जाने से पहले स्वचालित पूर्ण-सूट PHPUnit गेट।</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. परिवर्तन प्रबंधन &amp; परीक्षण</h2>
      <p>परिवर्तन शासन CC8 के साथ ट्रैक किए गए परिवर्तनों, अनुमोदनों और परीक्षण साक्ष्यों के साथ संरेखित है।</p>
      <ul class="doc-fact-list">
        <li><strong>परिवर्तन रिकॉर्ड:</strong> 12</li>
        <li><strong>अनुमोदन रिकॉर्ड:</strong> 10</li>
        <li><strong>परीक्षण परिणाम:</strong> 1528 परीक्षण, 8351 assertions (पास)</li>
        <li><strong>परीक्षण-नियंत्रण ट्रेस:</strong> 5 suites, 5 पास, 8 लिंक की गई परीक्षण फ़ाइलें</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. ऑडिट ट्रेल &amp; साक्ष्य अखंडता</h2>
      <p>प्रशासनिक और सुरक्षा-प्रासंगिक रनटाइम घटनाओं को अखंडता जाँचों के लिए अपरिवर्तनीय-लेजर सत्यापन के साथ निर्यात किया जाता है।</p>
      <p><strong>वर्तमान लेजर अखंडता स्थिति:</strong> पास।</p>
    </section>

    <section class="doc-section success">
      <h2>6. निरंतर निगरानी &amp; ताजगी</h2>
      <p>साक्ष्य निर्यात निरंतर चलते हैं और एक नियतात्मक ताजगी नीति के विरुद्ध सत्यापित किए जाते हैं।</p>
      <p><strong>वर्तमान ताजगी परिणाम:</strong> सभी मैप किए गए आर्टिफैक्ट 35-दिन की ऑडिट विंडो के भीतर हैं।</p>
    </section>

    <section class="doc-section">
      <h2>7. वर्तमान स्थिति</h2>
      <p><strong>स्थिति:</strong> निरंतर नियंत्रण हार्डनिंग और नियतात्मक साक्ष्य अपडेट के साथ SOC 2 तैयारी प्रगति में है।</p>
      <p>PayCal इस पृष्ठ पर SOC 2 प्रमाणन या ऑडिटर की राय का दावा नहीं करता। आधिकारिक रिपोर्ट तक पहुँच NDA-गेटेड रहती है।</p>
    </section>

    <section class="doc-section">
      <h2>पुन: उपयोग योग्य अनुपालन स्निपेट</h2>
      <p><strong>फ़ुटर बैज:</strong> तैयारी जारी है • नियंत्रण मैप किए गए • निरंतर साक्ष्य निगरानी</p>
      <p><strong>सारांश ब्लॉक:</strong> CC1-CC9 मैप किए गए, 37 आर्टिफैक्ट, 26 नियंत्रण लिंक, लेजर अखंडता पास, और स्वचालित पूर्ण-सूट परीक्षण साक्ष्य।</p>
    </section>

    <section class="doc-section highlight">
      <h2>संदर्भ</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">स्वच्छ सार्वजनिक नियंत्रण सारांश, नियतात्मक आख्यान और सुरक्षा संपर्क पथ।</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">PayCal SOC 2 सारांश</a>
          <span class="doc-ref-desc">इस रिपोर्ट के लिए स्थिति, मेट्रिक्स और NDA पहुँच।</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">SOC 2 रिपोर्ट का अनुरोध करें (NDA)</a>
          <span class="doc-ref-desc">विक्रेता और सुरक्षा due diligence समीक्षा के लिए गेटेड पहुँच।</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — आधिकारिक मानक</a>
          <span class="doc-ref-desc">SOC 2 मानदंड परिभाषित करने वाला आधिकारिक ढाँचा।</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">System and Organization Controls के इतिहास और दायरे का अवलोकन।</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Reddit समुदाय</a>
          <span class="doc-ref-desc">SOC 2 ऑडिट और तैयारी पर व्यवसायी चर्चा।</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
