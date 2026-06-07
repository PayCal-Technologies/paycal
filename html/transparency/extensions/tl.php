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
$pageTitle = 'Paradigma ng mga Extension - [PayCal]';
$pageLabel = 'Paradigma ng mga Extension';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Paradigma ng mga Extension</span>
  </nav>

  <header class="doc-article-header">
    <h1>Paradigma ng mga Extension</h1>
    <p class="deck">
      Ang PayCal ay dinisenyo upang manatiling matatag ang pangunahing lohika ng negosyo habang
      ang mga extension layer ay maaaring mag-angkop ng mga tampok para sa iba't ibang deployment
      at diskarte ng produkto.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Core-First na Arkitektura</h2>
      <p>
        Ang <strong>PayCal Core</strong> ay naglalaman ng canonical na domain at controller logic:
        mga kalkulasyon, pagpapatunay, mga pahintulot, patakaran sa lifecycle, at mga shared na API contract.
      </p>
      <p>
        Ang Core ay nananatiling extension-agnostic sa pamamagitan ng disenyo. Ang mga integration
        point ay naka-isolate sa pamamagitan ng mga bridge contract upang ang mga Core service ay
        masubok nang hiwalay sa mga runtime-specific na pakete.
      </p>
    </section>

    <section class="doc-section">
      <h2>Mga Pangunahing Extension na Kasama sa Repositoryo na Ito</h2>
      <p>
        Ang repositoryong ito ay nagpapadala ng <strong>mga pangunahing extension implementation</strong>
        na nagbibigay ng default na gawi para sa mga extension seam. Nagsisilbi ito bilang mga
        pampublikong reference package at ligtas na mga default para sa mga self-hosted na deployment.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> mga baseline billing capability hook at pagpili ng mode</li>
        <li><strong>earnings-ytd:</strong> baseline YTD rendering at mga earnings hook point</li>
        <li><strong>organization-signals:</strong> mga baseline organization signal hook</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Modelo ng Extension ng Ikatlong Partido</h2>
      <p>
        Ang mga ikatlong partido na gumagamit ng repositoryong ito ay maaaring lumikha at
        mapanatili ang kanilang sariling mga extension package. Ang inirerekomendang modelo ay:
      </p>
      <ol class="doc-list">
        <li>Panatilihing hindi binago ang core logic hangga't maaari</li>
        <li>Ipatupad ang custom na gawi sa mga extension package</li>
        <li>I-bind ang mga custom na pakete sa pamamagitan ng dokumentadong extension bootstrap at hook seam</li>
        <li>Pangalagaan ang mga Core contract upang mapamahalaan ang mga upstream upgrade</li>
      </ol>
      <p>
        Pinapayagan nito ang mga mapagkumpitensya at vertical-specific na deployment nang hindi
        pinipilit ang mga pangmatagalang fork ng pangunahing domain code.
      </p>
    </section>

    <section class="doc-section">
      <h2>Pagkakaiba ng Canonical na paycal.app Platform</h2>
      <p>
        Ang canonical na <code>https://paycal.app</code> platform ay nagpapatakbo ng
        <strong>mga private na extension variant</strong> sa ibabaw ng parehong Core at
        pangunahing extension paradigma.
      </p>
      <p>
        Ang mga private na variant na ito ay isang sinasadyang layer ng pagkakaiba ng produkto
        para sa mga kapaligiran na pinapatakbo ng PayCal. Maaari nilang i-tune ang mga workflow,
        gawi ng kakayahan, at mga UI-specific na integration habang pinapanatili ang
        compatibility sa parehong pangunahing arkitektura.
      </p>
      <ul class="doc-list">
        <li>Ang Core logic ay nananatiling shared at naaaudit</li>
        <li>Ang mga pampubliko/pangunahing extension ay nananatiling available sa repositoryo</li>
        <li>Ang mga private na extension ay nagbibigay ng pagkakaiba ng canonical na platform</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Mga Pangako sa Transparency</h2>
      <ul class="doc-list">
        <li>Ang mga Core contract ay dokumentado at nasubok sa mga extension seam</li>
        <li>Ang mga hangganan ng bridge ay malinaw upang makita ang coupling</li>
        <li>Ang gawi ng extension ay maaaring umunlad nang hindi nagde-destabilize ng mga Core service</li>
        <li>Ang mga self-hosted adopter ay malaya na bumuo ng mga alternatibong diskarte sa extension</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
