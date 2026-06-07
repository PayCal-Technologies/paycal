<?php
/**
 * Public Transparency: Extensions Paradigm
 *
 * PURPOSE:
 * Explain how PayCal separates core logic from extension layers, how third
 * parties can build custom extensions from this repository, and how
 * canonical paycal.app differentiates through private extension packages.
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
$pageTitle = 'एक्सटेंशन पैराडाइम - [PayCal]';
$pageLabel = 'एक्सटेंशन पैराडाइम';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">एक्सटेंशन पैराडाइम</span>
  </nav>

  <header class="doc-article-header">
    <h1>एक्सटेंशन पैराडाइम</h1>
    <p class="deck">
      PayCal को इस प्रकार डिज़ाइन किया गया है कि मुख्य व्यावसायिक तर्क स्थिर रहे जबकि
      एक्सटेंशन परतें विभिन्न तैनाती और उत्पाद रणनीतियों के लिए सुविधाओं को अनुकूलित कर सकती हैं।
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>कोर-फर्स्ट आर्किटेक्चर</h2>
      <p>
        <strong>PayCal Core</strong> में कैनोनिकल डोमेन और कंट्रोलर लॉजिक शामिल है:
        गणनाएँ, सत्यापन, अनुमतियाँ, जीवनचक्र नीति, और साझा API अनुबंध।
      </p>
      <p>
        Core डिज़ाइन द्वारा एक्सटेंशन-अज्ञेयवादी रहता है। एकीकरण बिंदु ब्रिज अनुबंधों के
        माध्यम से अलग किए जाते हैं ताकि Core सेवाओं को रनटाइम-विशिष्ट पैकेजों से
        स्वतंत्र रूप से परीक्षण किया जा सके।
      </p>
    </section>

    <section class="doc-section">
      <h2>इस रिपॉजिटरी में शामिल बुनियादी एक्सटेंशन</h2>
      <p>
        यह रिपॉजिटरी <strong>बुनियादी एक्सटेंशन कार्यान्वयन</strong> प्रदान करती है जो
        एक्सटेंशन बिंदुओं के लिए डिफ़ॉल्ट व्यवहार प्रदान करते हैं। ये सार्वजनिक संदर्भ पैकेजों
        और स्व-होस्टेड तैनाती के लिए सुरक्षित डिफ़ॉल्ट के रूप में काम करते हैं।
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> आधारभूत बिलिंग क्षमता हुक और मोड चयन</li>
        <li><strong>earnings-ytd:</strong> आधारभूत YTD रेंडरिंग और आय हुक पॉइंट</li>
        <li><strong>organization-signals:</strong> आधारभूत संगठन संकेत हुक</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>तृतीय-पक्ष एक्सटेंशन मॉडल</h2>
      <p>
        इस रिपॉजिटरी का उपयोग करने वाले तृतीय पक्ष अपने स्वयं के एक्सटेंशन पैकेज बना
        और बनाए रख सकते हैं। अनुशंसित मॉडल है:
      </p>
      <ol class="doc-list">
        <li>जब भी संभव हो Core लॉजिक को अपरिवर्तित रखें</li>
        <li>एक्सटेंशन पैकेजों में कस्टम व्यवहार लागू करें</li>
        <li>दस्तावेज़ीकृत एक्सटेंशन बूटस्ट्रैप और हुक बिंदुओं के माध्यम से कस्टम पैकेज बाइंड करें</li>
        <li>Core अनुबंधों को सुरक्षित रखें ताकि अपस्ट्रीम अपग्रेड प्रबंधनीय रहें</li>
      </ol>
      <p>
        यह केंद्रीय डोमेन कोड के दीर्घकालिक फोर्क को बिना बाध्य किए प्रतिस्पर्धी और
        वर्टिकल-विशिष्ट तैनाती की अनुमति देता है।
      </p>
    </section>

    <section class="doc-section">
      <h2>कैनोनिकल paycal.app प्लेटफ़ॉर्म विभेदन</h2>
      <p>
        कैनोनिकल <code>https://paycal.app</code> प्लेटफ़ॉर्म उसी Core और बुनियादी एक्सटेंशन
        पैराडाइम के ऊपर <strong>निजी एक्सटेंशन वेरिएंट</strong> चलाता है।
      </p>
      <p>
        ये निजी वेरिएंट PayCal-संचालित वातावरणों के लिए एक जानबूझकर उत्पाद विभेदन परत हैं।
        वे उसी मुख्य आर्किटेक्चर के साथ संगतता बनाए रखते हुए वर्कफ़्लो, क्षमता व्यवहार
        और UI-विशिष्ट एकीकरण को समायोजित कर सकते हैं।
      </p>
      <ul class="doc-list">
        <li>Core लॉजिक साझा और ऑडिट-योग्य बना रहता है</li>
        <li>सार्वजनिक/बुनियादी एक्सटेंशन रिपॉजिटरी में उपलब्ध रहते हैं</li>
        <li>निजी एक्सटेंशन कैनोनिकल प्लेटफ़ॉर्म विभेदन प्रदान करते हैं</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>पारदर्शिता प्रतिबद्धताएँ</h2>
      <ul class="doc-list">
        <li>Core अनुबंध एक्सटेंशन बिंदुओं पर दस्तावेज़ीकृत और परीक्षित हैं</li>
        <li>ब्रिज सीमाएँ स्पष्ट हैं ताकि कपलिंग खोजने योग्य हो</li>
        <li>एक्सटेंशन व्यवहार Core सेवाओं को अस्थिर किए बिना विकसित हो सकता है</li>
        <li>स्व-होस्टेड अपनाने वाले वैकल्पिक एक्सटेंशन रणनीतियाँ बनाने के लिए स्वतंत्र हैं</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
