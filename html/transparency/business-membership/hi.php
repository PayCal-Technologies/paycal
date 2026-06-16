<?php
/**
 * Public Transparency: Organization Membership and Role Philosophy
 *
 * PURPOSE:
 * Explain why PayCal uses an Organization <-> Member relationship model,
 * how role changes are governed, and what architectural philosophy guides
 * capability, scope, and security decisions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">संगठन सदस्यता और भूमिका दर्शन</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      यह पृष्ठ शिथिल रूप से जुड़ी टीम सिमेंटिक्स से एक स्पष्ट संगठन <strong>&lt;-&gt;</strong> सदस्य
      संबंध मॉडल की ओर बदलाव, वर्तमान भूमिका नीति, और उन सिद्धांतों की व्याख्या करता है जिनका उपयोग
      हम अनुमतियों को ऑडिट-योग्य और सुरक्षित रखने के लिए करते हैं।
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>यह मॉडल क्यों अस्तित्व में है</h2>
      <p>
        पेरोल सहयोग का वास्तविक सुरक्षा प्रभाव होता है। एक भूमिका मॉडल जो पढ़ने, परीक्षण करने और
        ऑडिट करने में आसान हो, बिखरी हुई एकल जाँचों से बने मॉडल से अधिक सुरक्षित है।
      </p>
      <p>
        संगठन <strong>&lt;-&gt;</strong> सदस्य संरचना प्रत्येक अभिकर्ता को नीति-जागरूक स्थिति, भूमिका
        और दायरे के व्यवहार के साथ एक संगठन से स्पष्ट संबंध देती है।
      </p>
    </section>

    <section class="doc-section">
      <h2>संगठन <strong>&lt;-&gt;</strong> सदस्य संबंध परिवर्तन</h2>
      <ul class="doc-list">
        <li>सदस्यता को एक अंतर्निहित UI स्थिति के बजाय एक स्पष्ट संबंध के रूप में दर्शाया जाता है।</li>
        <li>पहुँच-अनुरोध, आमंत्रण, अनुमोदन, सक्रियण और निरसन जीवनचक्र अवस्थाएँ बैकएंड नीति द्वारा लागू की जाती हैं।</li>
        <li>संगठन पैनल और सूचनाएँ अब संबंध संक्रमणों और भूमिका परिणामों को अधिक सुसंगत रूप से दर्शाती हैं।</li>
        <li>साझा संगठन व्यवहार विशेषाधिकार प्राप्त क्रियाओं के संसाधन से पहले सदस्यता अवस्था द्वारा नियंत्रित होता है।</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>भूमिका परिवर्तन और वर्तमान भूमिका दर्शन</h2>
      <p>
        भूमिकाएँ क्षमता-संचालित हैं, प्रति ऑपरेशन दायरे प्रतिबंध लागू होते हैं। वर्तमान आधार:
      </p>
      <ul class="doc-list">
        <li><strong>स्वामी:</strong> स्वामित्व हस्तांतरण और उच्च-विश्वास शासन क्रियाओं सहित संप्रभु नियंत्रण।</li>
        <li><strong>प्रबंधक:</strong> स्वामित्व हस्तांतरण प्राधिकरण के बिना दैनिक परिचालन नियंत्रण।</li>
        <li><strong>योगदानकर्ता:</strong> असाइन किए गए दायरे से प्रतिबंधित लेखन प्राधिकरण वाला विश्वसनीय ऑपरेटर।</li>
        <li><strong>सदस्य:</strong> प्रतिबंधित परिवर्तन अधिकारों के साथ सीमित स्व-सेवा भागीदारी।</li>
        <li><strong>दर्शक:</strong> लेखन अनुमतियों के बिना केवल-पढ़ने योग्य दृश्यता।</li>
      </ul>
      <p>
        हम अतिभारित भूमिका फ्लैग से अधिक स्पष्ट क्षमता और दायरे की संरचना को प्राथमिकता देते हैं। यह भूमिका परिणामों को परीक्षण और समझने में आसान बनाता है।
      </p>
    </section>

    <section class="doc-section">
      <h2>सुरक्षा और एन्क्रिप्शन दर्शन</h2>
      <p>
        संगठन सहयोग एन्क्रिप्शन और सहमति नियंत्रणों के साथ प्रतिच्छेद करता है। सदस्यता और भूमिका जाँच
        साझा संगठन एनवलप व्यवहार को नियंत्रित करती है ताकि संवेदनशील ऑपरेशन नीति-बद्ध रहें।
      </p>
      <ul class="doc-list">
        <li>सदस्यता और सहमति अवस्था साझा सुरक्षित ऑपरेशन आगे बढ़ने से पहले सत्यापित की जाती है।</li>
        <li>भूमिका परिवर्तन और सदस्यता संक्रमणों को सुरक्षा-प्रासंगिक घटनाओं के रूप में माना जाता है, केवल UX घटनाओं के रूप में नहीं।</li>
        <li>पहुँच इनकार पथ नीति बेमेल के तहत अपेक्षित व्यवहार हैं और ऑडिटबिलिटी के लिए उजागर किए जाते हैं।</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>आगे की परिचालन दर्शन</h2>
      <ul class="doc-list">
        <li><strong>एकल नीति स्रोत:</strong> भूमिका और दायरे के निर्णय साझा बैकएंड नीति मानचित्रों से उत्पन्न होने चाहिए।</li>
        <li><strong>UI प्रक्षेपण के रूप में:</strong> इंटरफ़ेस को प्राधिकरण तर्क को दोहराने के बजाय नीति परिणाम प्रदर्शित करने चाहिए।</li>
        <li><strong>ट्रेस करने योग्य संक्रमण:</strong> अनुमोदन, भूमिका परिवर्तन और निरसन अवलोकनीय और समीक्षा योग्य रहने चाहिए।</li>
        <li><strong>रिलीज़ पारदर्शिता:</strong> सदस्यता और भूमिकाओं में व्यवहार परिवर्तन चेंजलॉग और पारदर्शिता पृष्ठों में प्रलेखित हैं।</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
