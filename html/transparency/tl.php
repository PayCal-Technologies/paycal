<?php
/**
 * Public Transparency Hub — Filipino / Tagalog
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

$readMoreLabel = 'Magbasa pa';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Sentro ng Transparency - [PayCal]';
$pageLabel = 'Sentro ng Transparency';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">Sentro ng Transparency</span>
    </nav>

    <header class="doc-article-header">
      <h1>Sentro ng Transparency</h1>
      <p class="deck">Inilalathala namin kung paano gumagana ang PayCal para mapatunayan ng mga gumagamit ang mga desisyon — hindi lamang magtiwala sa mga pahayag.</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>Pilosopiya ng Platform</h2>
        <p>Ang PayCal ay itinayo sa paligid ng mga operasyong maaaring siyasatin: ang mga formula ay dokumentado, ang mga limitasyon sa telemetry ay malinaw, at ang pagpapanatili ng data ay may hangganan bilang default.</p>
        <p>Ang aming prinsipyo ay simple: kung ang isang sistema ay nakakaapekto sa payroll o privacy, dapat na maintindihan ng mga gumagamit kung paano ito gumagana at kung paano ito pinamamahalaan.</p>
        <p>Ang subscription billing ay pinoproseso ng Stripe. Available ang Stripe support sa <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a>.</p>
        <p>Ang mga kamakailang update na humubog sa produkto — kabilang ang mga daloy ng billing at profile governance — ay sinusubaybayan sa aming mga pahina ng framework/backend at test governance.</p>
      </section>

      <div class="doc-panel-grid doc-panel-grid--responsive-3" aria-label="Mga panel ng detalye ng transparency">
        <section class="doc-section">
          <h2>Katayuan ng Security Audit</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>Inilalathala ng pahinang ito ang kasalukuyang katayuan ng audit, ang saradong saklaw, mga sanggunian sa ebidensya, at mga pangako sa pagharang ng release na nagpapanatili ng postura ng seguridad.</p>
          <ul class="doc-fact-list">
            <li>Ang katayuan ng kasalukuyang ikot ay inilalathala kasama ang petsa ng pag-verify at dalas ng pagsusuri.</li>
            <li>Ang saklaw ng coverage ay kinabibilangan ng mga kontrol sa lifecycle ng runtime, paghihiwalay ng telemetry, governance ng correlation, at pagpapatibay ng mga pribilehiyong tungkulin.</li>
            <li>Kasama sa validation snapshot ang Playwright, JS, PHPStan antas 9, at mga resulta ng backend test.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Mga Metrics ng Platform at Privacy</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>Ipinapaliwanag ng pahina ng mga metrics ang operational telemetry na nakolekta para sa pagiging maaasahan at pagpaplano ng kapasidad.</p>
          <ul class="doc-fact-list">
            <li>Ang mga telemetry key at mga halimbawa ay inilalathala para mapatunayan ang mga pahayag.</li>
            <li>Ang saklaw ng koleksyon ay pinagsama-sama lamang, na may mahigpit na mga limitasyon at walang mga personal na identifier sa mga key.</li>
            <li>Ang pagpapanatili ay sumusunod sa isang tinukoy na lifecycle: raw data, aggregates, at awtomatikong purge.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Accessibility at Pagsunod sa WCAG</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Gumagamit kami ng WCAG 2.1 antas AA bilang aming pamantayan sa accessibility at inilalathala ang kamakailang gawain sa accessibility sa simpleng wika.</p>
          <ul class="doc-fact-list">
            <li>Sinusuportahan ng pangunahing navigation ang paggamit ng keyboard, mga skip link, at dokumentadong single-key shortcut para sa mga pangunahing destinasyon.</li>
            <li>Ang paghawak ng shortcut ay liguro at hindi nagpapatakbo habang nagta-type sa mga field na maaaring i-edit o kapag bukas ang mga dialog.</li>
            <li>Ang kamakailang regression coverage ay nagbe-verify ng mga heading, reflow/text spacing, mga landas ng navigation, at ang accessibility feedback handoff.</li>
            <li>Ang mahigpit na route-level na mga blokoador ng contrast sa mga pangunahing pampublikong pahina ay naayos na; patuloy ang gawain sa contrast sa buong tema.</li>
            <li>Maaaring magsimula ang mga gumagamit ng accessibility report mula sa pahina ng accessibility at ipagpatuloy ito sa pamamagitan ng ligurong contact flow.</li>
            <li>Inilalathala na ngayon ng pahina ng transparency ng accessibility ang petsa ng huling pag-verify, saklaw ng pag-verify, mga kilalang limitasyon, at susunod na petsa ng pagsusuri.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Verification at Governance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Idodokumento ng pahinang ito kung paano nagpapatupad ang PayCal ng mga patakaran sa pamamagitan ng mga test, hook, runtime limit, at kontrol sa seguridad.</p>
          <ul class="doc-fact-list">
            <li>Ang mga pre-commit at pre-push hook ay nagpapatupad ng PHPStan antas 9 at tinatanggihan ang mga baseline bypass.</li>
            <li>Nagpapatakbo ang CI ng staged validation sa mga unit, integration, contract, random-order, at coverage job.</li>
            <li>Inaaplika ng mga runtime control ang mga rate limit, TTL window, at mga bloke ng pagtugon sa pang-aabuso para sa mga sensitibong daloy.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Mga Kakayahan sa Network</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Inilalathala ng artikulong ito ang mga transport protocol at mga kontrol sa response header na ginagamit para ma-secure ang gawi ng browser at network.</p>
          <ul class="doc-fact-list">
            <li>Idodokumento ang pagpapatupad ng HTTPS, HSTS preload, at pag-advertise ng HTTP/3 (QUIC).</li>
            <li>Inilalista ang kasalukuyang baseline ng mga security header kabilang ang CSP, COOP, COEP, CORP, at mga browser hardening header.</li>
            <li>Ipinaliliwanag ang protocol negotiation at fallback behavior sa mga modernong kliyente.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Test Governance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Idodokumento ng artikulong ito kung paano kami nagpapatakbo ng backend, frontend, at accessibility validation at kung aling mga gate ang tinatrato bilang mga release blocker.</p>
          <ul class="doc-fact-list">
            <li>Ipinapakita ang aktibong imbentaryo ng PHPUnit suite at ang pagkakahati ayon sa kategorya.</li>
            <li>Idodokumento ang mga release-blocking validation command na ginagamit sa mga <code>/mis</code> sweep.</li>
            <li>Ipinaliliwanag kung paano nasisingkronisa ang ebidensya ng test sa mga changelog at source-of-truth na tala.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Dependency at CI/CD Governance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>Inilalathala ng artikulong ito kung paano kinokontrol ang mga npm dependency at kung paano ipinapatupad ang mga CI gate bago mag-release.</p>
          <ul class="doc-fact-list">
            <li>Idodokumento ang patakaran ng lockfile-first npm at ang mga kinakailangan sa automation ng <code>npm ci</code>.</li>
            <li>Inimamapa ang mga JavaScript quality gate at mga yugto ng backend pipeline sa mga workflow control.</li>
            <li>Inilalista ang mga kilalang limitasyon sa dokumentasyon at mga nakaplanong pagpapabuti sa governance.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Framework at Backend Change Log</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Sinusubaybayan ng pahinang ito ang arkitektura ng backend at mga pagbabago sa antas ng framework na may mga pampublikong paliwanag kung ano ang nagbago at bakit.</p>
          <ul class="doc-fact-list">
            <li>Ibinubuod ang mga pagbabago sa serbisyo/controller na materyal na nakakaapekto sa gawi.</li>
            <li>Inimamapa ang mga pagbabago sa release sa mga kontrol sa seguridad at governance.</li>
            <li>Kinabibilangan ng mga sanggunian sa detalyadong changelog at mga artifact ng audit.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Karanasan sa Produkto at Mga Pagbabago sa Billing</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Ang mga pangunahing update sa account, billing, at profile-flow ay ipinaliliwanag kasama ng backend at test governance para ma-audit ng mga gumagamit ang parehong mga pagbabago sa UX at gawi.</p>
          <ul class="doc-fact-list">
            <li>Sinusubaybayan ang paghawak ng billing-state at mga pagbabago sa kontrata ng status ng subscription.</li>
            <li>Kinakunan ang mga pangkaligtasan para sa mga mapanirang aksyon tulad ng mga eksplisitong parirala ng kumpirmasyon ng pagtanggal ng account.</li>
            <li>Iniuugnay ang mga update na nakaharap sa produkto sa ebidensya ng verification at release governance.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Pamamaraan ng Buwis</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>Idodokumento ng pahina ng buwis ang aming mga CRA-aligned na formula, threshold, at halimbawa na ginagamit para sa mga pagtatantya.</p>
          <ul class="doc-fact-list">
            <li>Ang mga formula ng CPP, OAS, EI, pederal/probinsyal na buwis, at net-pay ay dokumentado na may mga halimbawa.</li>
            <li>Ang mga kasalukuyang threshold at rate ng taon ng buwis ay inilalathala at naka-link sa mga sanggunian ng CRA.</li>
            <li>Ang kalidad ng pagkalkula ay bine-validate gamit ang isang automated test suite at taunang pag-update ng rate.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Arkitektura ng Email</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>Ipinaliliwanag ng pahina ng email kung aling mga transaksyonal na email ang ipinapadala ng PayCal, kung paano inirender ang mga template, at kung paano bine-verify ang pagiging maaasahan ng paghahatid.</p>
          <ul class="doc-fact-list">
            <li>Ang mga pamilya ng template na tiyak sa daloy ay dokumentado para sa mga landas ng verification, recovery, pagpapalit ng email, at contact support.</li>
            <li>Ang mga responsibilidad sa paghahatid ay hiwalay sa pagitan ng EmailGarum orchestration at ng EmailTransport SMTP protocol handling.</li>
            <li>Ang mga opt-in na live test para sa mga template sweep at pag-verify ng kalusugan ng DKIM/DMARC ay dokumentado.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Load Testing ng Earnings</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Inilalathala ng artikulong ito ang mga reproducible na resulta ng A/B benchmark para sa eager rendering kumpara sa lazy section loading sa <code>/earnings/</code>.</p>
          <ul class="doc-fact-list">
            <li>Kinabibilangan ng 10-run matrix para sa mga tunay at synthetic na dataset ng 2025/2026.</li>
            <li>Iniuulat ang DOMContentLoaded, oras ng handa ng seksyon, at mga trade-off ng API call.</li>
            <li>Idodokumento ang paraan ng pagsubok at interpretasyon para sa pampublikong pagsusuri.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Mapa ng mga Superhero</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>Idodokumento ng pahina ng mga Superhero ang mga temadong cross-cutting component ng PayCal at ang partikular na operational na problema na nilulutas ng bawat isa.</p>
          <ul class="doc-fact-list">
            <li>Kinabibilangan ng ShadowTalon, Guardian, Phantom Wing, Lens, at EmailGarum.</li>
            <li>Ipinaliliwanag kung saan ginagamit ang bawat component at kung anong hangganan ng panganib ang pinoprotektahan nito.</li>
            <li>Nagbibigay ng mga verification anchor para masuri ang mga pahayag ng implementasyon nang direkta sa code at mga test.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
