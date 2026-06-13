<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_SOC2_PAGE_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'];


require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars($i18n['BREADCRUMB'], ENT_QUOTES, 'UTF-8'); ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Sentro ng Transparency</a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">Isang teknikal na pananaw kung paano ini-map ng PayCal ang mga kontrol ng SOC 2 sa mga isinasagawang gawi ng sistema at patuloy na nabubuong katibayan.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Pangkalahatang-ideya</h2>
      <p>Nagpapatakbo ang PayCal ng programa ng seguridad na nakahanay sa SOC 2, nakatuon sa napatunayang pagpapatupad at mga nata-trace na katibayan, hindi sa mga pahayag na batay lamang sa patakaran.</p>
      <ul class="doc-fact-list">
        <li><strong>Mga kontrol sa saklaw:</strong> CC1-CC9</li>
        <li><strong>Mga artefakto sa kasalukuyang bundle:</strong> 37</li>
        <li><strong>Mga kontrol-sa-artefakto na pagmamapa:</strong> 26</li>
        <li><strong>Window ng kasariwaan ng katibayan:</strong> 35 araw</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Saklaw ng Kontrol (CC1-CC9)</h2>
      <p>Lahat ng SOC 2 Common Criteria na kontrol sa saklaw (CC1 hanggang CC9) ay ini-map sa mga katibayan na nanatili sa buwanang bundle.</p>
      <p>Sinusuportahan ng pagmamapang ito ang direktang traceability mula sa layunin ng kontrol hanggang sa mga kongkretong artefakto na ginagamit para sa pagsusuri.</p>
    </section>

    <section class="doc-section">
      <h2>3. Paano Isinasagawa ang mga Kontrol</h2>
      <p>Tinatrato ng PayCal ang pagpapatupad bilang katangian ng sistema. Ang mga kontrol ay programatikong isinasagawa, hindi lamang naidodokumento.</p>
      <ul class="doc-fact-list">
        <li><strong>Pagpapatunay:</strong> Passkey-capable na daloy ng pagpapatunay upang palakasin ang resistensya sa phishing.</li>
        <li><strong>Integridad sa runtime:</strong> Pagsubaybay sa integridad sa runtime na may pamamahala ng estado ng operasyon.</li>
        <li><strong>Pagpapatibay ng output:</strong> Mga kontrol ng sanitization ng Guardian para sa sensitibong mga landas ng DOM/output.</li>
        <li><strong>Hadlang ng kalidad:</strong> Awtomatikong buong-suite na PHPUnit na hadlang bago tanggapin ang katibayan ng bundle.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Pamamahala ng Pagbabago &amp; Pagsubok</h2>
      <p>Ang pamamahala ng pagbabago ay nakahanay sa CC8 na may mga sinusubaybayan na pagbabago, mga pag-apruba, at katibayan ng pagsubok.</p>
      <ul class="doc-fact-list">
        <li><strong>Mga talaan ng pagbabago:</strong> 12</li>
        <li><strong>Mga talaan ng pag-apruba:</strong> 10</li>
        <li><strong>Mga resulta ng pagsubok:</strong> 1528 na pagsubok, 8351 assertion (naipasa)</li>
        <li><strong>Tracing ng pagsubok-kontrol:</strong> 5 suite, 5 naipasa, 8 naka-link na file ng pagsubok</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Audit Trail &amp; Integridad ng Katibayan</h2>
      <p>Ang mga administratibo at may kaugnayan sa seguridad na kaganapan sa runtime ay ini-export na may immutable-ledger na pagpapatunay para sa mga pagsusuri ng integridad.</p>
      <p><strong>Kasalukuyang katayuan ng integridad ng ledger:</strong> NAIPASA.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Patuloy na Pagsubaybay &amp; Kasariwaan</h2>
      <p>Ang mga export ng katibayan ay patuloy na tumatakbo at napatunayan laban sa isang deterministikong patakaran ng kasariwaan.</p>
      <p><strong>Kasalukuyang resulta ng kasariwaan:</strong> lahat ng mga naka-map na artefakto ay nasa loob ng 35-araw na window ng audit.</p>
    </section>

    <section class="doc-section">
      <h2>7. Kasalukuyang Katayuan</h2>
      <p><strong>Katayuan:</strong> Ang kahandaan sa SOC 2 ay kasalukuyang isinasagawa, na may patuloy na pagpapatibay ng kontrol at mga deterministikong update ng katibayan.</p>
      <p>Hindi nag-aangkin ang PayCal ng sertipikasyon ng SOC 2 o opinyon ng auditor sa pahinang ito. Ang pormal na access sa ulat ay nananatiling nakatali sa NDA.</p>
    </section>

    <section class="doc-section">
      <h2>Mga Muling Nagagamit na Snippet ng Pagsunod</h2>
      <p><strong>Badge ng footer:</strong> Ang pagiging handa ay nagpapatuloy • Mga Kontrol na Na-map • Patuloy na Pagsubaybay sa Katibayan</p>
      <p><strong>Bloke ng buod:</strong> CC1-CC9 na na-map, 37 artefakto, 26 link ng kontrol, naipasang integridad ng ledger, at awtomatikong buong-suite na katibayan ng pagsubok.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Mga Sanggunian</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Nililinis na pampublikong buod ng kontrol, mga deterministikong salaysay, at landas ng pakikipag-ugnayan sa seguridad.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">PayCal SOC 2 Buod</a>
          <span class="doc-ref-desc">Katayuan, mga sukatan, at access sa NDA para sa ulat na ito.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">Humiling ng SOC 2 na Ulat (NDA)</a>
          <span class="doc-ref-desc">Pinigilan na access para sa pagsusuri ng due diligence ng vendor at seguridad.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Opisyal na Pamantayan</a>
          <span class="doc-ref-desc">Ang makapangyarihang balangkas na nagtatakda ng mga pamantayan ng SOC 2.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Pangkalahatang-ideya ng kasaysayan at saklaw ng System and Organization Controls.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Reddit Community</a>
          <span class="doc-ref-desc">Talakayan ng mga practitioner tungkol sa mga audit ng SOC 2 at paghahanda.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
