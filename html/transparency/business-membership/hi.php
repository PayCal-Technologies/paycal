<?php
/**
 * Public Transparency: Business Connections and Role Philosophy
 *
 * PURPOSE:
 * Explain how PayCal separates business connections, active membership,
 * role changes, consent, and explicit access grants.
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
    <span class="current">व्यावसायिक कनेक्शन और भूमिका दर्शन</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      यह पृष्ठ शिथिल रूप से जुड़ी टीम सिमेंटिक्स से स्पष्ट Connections की ओर बदलाव समझाता है।
      Connection बताता है कि कौन किससे जुड़ा है। सदस्यता, भूमिका, सहमति और protected data access
      अलग-अलग policy decisions बने रहते हैं।
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/business-membership-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>यह मॉडल क्यों अस्तित्व में है</h2>
      <p>
        पेरोल सहयोग का वास्तविक सुरक्षा प्रभाव होता है। एक भूमिका मॉडल जो पढ़ने, परीक्षण करने और
        ऑडिट करने में आसान हो, बिखरी हुई एकल जाँचों से बने मॉडल से अधिक सुरक्षित है।
      </p>
      <p>
        Business <strong>&lt;-&gt;</strong> Member connection हर actor को business से स्पष्ट identity link देता है।
        Active membership, role authority, protected data consent और भविष्य के person-to-person grants
        उस link से अलग रहते हैं।
      </p>
    </section>

    <section class="doc-section">
      <h2>Business <strong>&lt;-&gt;</strong> Member Connection परिवर्तन</h2>
      <ul class="doc-list">
        <li>Connections को UI state से अनुमानित करने के बजाय स्पष्ट रूप से दर्शाया जाता है।</li>
        <li>पहुँच-अनुरोध, आमंत्रण, अनुमोदन, सक्रियण और निरसन जीवनचक्र अवस्थाएँ बैकएंड नीति द्वारा लागू की जाती हैं।</li>
        <li>Business panels और notifications अब connection transitions और role outcomes को अधिक consistent रूप से दिखाते हैं।</li>
        <li>Shared Business behavior privileged actions से पहले active membership और role policy द्वारा नियंत्रित होता है।</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Connection, membership, consent और grants</h2>
      <p>
        PayCal अब इन concepts को अलग-अलग रखता है:
      </p>
      <ul class="doc-list">
        <li><strong>Connection:</strong> किसी person और business के बीच, या दो people के बीच identity link.</li>
        <li><strong>Membership:</strong> workspace collaboration के लिए active Business participation state.</li>
        <li><strong>Consent:</strong> protected work data sharing के लिए member की approval.</li>
        <li><strong>Grant:</strong> explicit permission, जैसे delegated calendar viewing या भविष्य की trusted recovery capability.</li>
      </ul>
      <p>
        केवल connection protected reports, exports, payroll visibility, recovery authority,
        या किसी और person की ओर से act करने की ability नहीं देता।
      </p>
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
        Business collaboration encryption और consent controls से जुड़ा है। Active membership,
        role checks और consent state shared Business envelope behavior को नियंत्रित करते हैं
        ताकि sensitive operations policy-bound रहें।
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
