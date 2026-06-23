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
    <span class="current">Mga Koneksyon ng Negosyo at Pilosopiya ng Papel</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Ipinaliwanag ng pahinang ito ang paglipat mula sa maluwag na semantika ng koponan patungo
      sa malinaw na Connections. Sinasabi ng connection kung sino ang naka-link kanino. Ang membership,
      papel, consent, at access sa protected data ay magkakahiwalay na desisyon ng patakaran.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/business-membership-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Bakit Umiiral ang Modelong Ito</h2>
      <p>
        Ang pakikipagtulungan sa payroll ay may tunay na epekto sa seguridad. Ang isang modelo ng papel
        na madaling basahin, subukan, at i-audit ay mas ligtas kaysa sa isang modelong itinayo mula sa
        mga nakakalat na minsan-lang na pagsusuri.
      </p>
      <p>
        Ang Business <strong>&lt;-&gt;</strong> Member connection ay nagbibigay sa bawat aktor ng malinaw
        na identity link sa isang business. Ang active membership, role authority, consent sa protected
        data, at future person-to-person grants ay nananatiling hiwalay sa link na iyon.
      </p>
    </section>

    <section class="doc-section">
      <h2>Mga Pagbabago sa Business <strong>&lt;-&gt;</strong> Member Connection</h2>
      <ul class="doc-list">
        <li>Ang connections ay malinaw na kinakatawan sa halip na hulaan mula sa estado ng UI.</li>
        <li>Ang mga estado ng lifecycle — kahilingan ng access, imbitasyon, pag-apruba, pag-activate, at pagbabawi — ay isinasagawa ng patakaran ng backend.</li>
        <li>Ang Business panels at notifications ay mas consistent na nagpapakita ng connection transitions at role outcomes.</li>
        <li>Ang nakabahaging gawi ng Business ay pinamamahalaan ng active membership at role policy bago maproseso ang mga may pribilehiyong aksyon.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Connection, membership, consent, at grants</h2>
      <p>
        Hinihiwalay na ngayon ng PayCal ang mga konseptong ito:
      </p>
      <ul class="doc-list">
        <li><strong>Connection:</strong> identity link sa pagitan ng tao at business, o sa pagitan ng dalawang tao.</li>
        <li><strong>Membership:</strong> active Business participation state para sa workspace collaboration.</li>
        <li><strong>Consent:</strong> pag-apruba ng member na ibahagi ang protected work data.</li>
        <li><strong>Grant:</strong> malinaw na permission, gaya ng delegated calendar viewing o future trusted recovery capability.</li>
      </ul>
      <p>
        Ang connection lang ay hindi nagbibigay ng protected reports, exports, payroll visibility,
        recovery authority, o kakayahang kumilos para sa ibang tao.
      </p>
    </section>

    <section class="doc-section">
      <h2>Mga Pagbabago sa Papel at Kasalukuyang Pilosopiya ng Papel</h2>
      <p>
        Ang mga papel ay pinapatakbo ng kakayahan, na may mga paghihigpit ng saklaw na inilapat sa bawat operasyon. Ang kasalukuyang baseline:
      </p>
      <ul class="doc-list">
        <li><strong>may-ari:</strong> soberanong kontrol kabilang ang paglipat ng pagmamay-ari at mga aksyon ng pamamahala na may mataas na tiwala.</li>
        <li><strong>tagapamahala:</strong> araw-araw na kontrol sa operasyon nang walang awtoridad sa paglipat ng pagmamay-ari.</li>
        <li><strong>kontribyutor:</strong> pinagkakatiwalaang operator na may awtoridad sa pagsulat na limitado ng itinalagang saklaw.</li>
        <li><strong>miyembro:</strong> limitadong self-service na pakikilahok na may limitadong mga karapatang sa pagbabago.</li>
        <li><strong>manonood:</strong> read-only na kakayahang makita nang walang mga pahintulot sa pagsulat.</li>
      </ul>
      <p>
        Pinipili namin ang malinaw na komposisyon ng kakayahan at saklaw kaysa sa mga overloaded na flag ng papel. Ginagawa nitong mas madaling subukan at pag-isipan ang mga resulta ng papel.
      </p>
    </section>

    <section class="doc-section">
      <h2>Pilosopiya ng Seguridad at Encryption</h2>
      <p>
        Ang Business collaboration ay konektado sa encryption at consent controls. Active membership,
        role checks, at consent state ang nagtatakda ng shared Business envelope behavior upang ang
        sensitibong operations ay manatiling nakatali sa patakaran.
      </p>
      <ul class="doc-list">
        <li>Ang estado ng miyembro at pahintulot ay napatunayan bago magpatuloy ang mga nakabahaging ligtas na operasyon.</li>
        <li>Ang mga pagbabago sa papel at paglipat ng miyembro ay tinatrato bilang mga kaganapang may kaugnayan sa seguridad, hindi lamang mga kaganapan sa UX.</li>
        <li>Ang mga landas ng pagtanggi sa pag-access ay inaasahang gawi sa ilalim ng hindi pagkakatugma ng patakaran at inilalantad para sa auditability.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Pilosopiya ng Operasyon para sa Hinaharap</h2>
      <ul class="doc-list">
        <li><strong>Isang pinagkukunan ng patakaran:</strong> ang mga desisyon sa papel at saklaw ay dapat magmula sa mga nakabahaging mapa ng patakaran ng backend.</li>
        <li><strong>UI bilang proyeksyon:</strong> ang mga interface ay dapat magpakita ng mga resulta ng patakaran kaysa sa pag-duplicate ng lohika ng awtorisasyon.</li>
        <li><strong>Mga nata-trace na paglipat:</strong> ang mga pag-apruba, pagbabago ng papel, at pagbabawi ay dapat manatiling nakikita at nasusubukan.</li>
        <li><strong>Transparency sa release:</strong> ang mga pagbabago sa gawi sa miyembro at mga papel ay naidokumento sa mga changelog at pahina ng transparency.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
