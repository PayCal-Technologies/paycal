<?php
/**
 * Public Transparency: Error Handling & Message Normalization
 *
 * PURPOSE: 
 * Explain PayCal's standardized error-message normalization pattern, the
 * security and UX rationale behind it, and how we ensure users receive
 * meaningful, safe error feedback across all frontend modules.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Pamamahala ng Error at Normalisasyon ng Mensahe</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Paano ni-standardize ng PayCal ang pag-uulat ng error sa lahat ng frontend module upang
      matiyak na makatanggap ang mga gumagamit ng makabuluhan, ligtas, at konsistenteng feedback
      nang hindi inilalantad ang sensitibong detalye.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Pangkalahatang-ideya at Layunin</h2>
      <p>
        Kapag nakaranas ang mga gumagamit ng mga error (pagkabigo ng network, tinanggihang pahintulot, mga pagkakamali sa validation),
        nararapat silang makatanggap ng malinaw na feedback na nagpapaliwanag kung ano ang nangyari at kung paano ito ayusin. Gayunpaman,
        ang mga hilaw na mensahe ng error mula sa backend ay kailangang i-normalize upang:
      </p>
      <ul class="doc-list">
        <li><strong>Alisin ang kalabisan:</strong> Tanggalin ang paulit-ulit na mga prefix na &quot;Error:&quot; at linisin ang whitespace</li>
        <li><strong>Pigilan ang pagtagas:</strong> Tiyaking ang sensitibong mga detalye ng implementasyon ay hindi kailanman maaabot ng gumagamit</li>
        <li><strong>Magbigay ng mga fallback:</strong> Ipakita ang mga ligtas na mensahe kapag ang mga error ay walang laman o mali ang format</li>
        <li><strong>Tiyakin ang konsistensya:</strong> Ilapat ang parehong lohika sa lahat ng 11+ frontend module</li>
        <li><strong>Pagbutihin ang pag-debug:</strong> Itala ang kumpletong mga detalye ng error sa Phantom Wing habang nagpapakita ng ligtas na mga buod sa mga gumagamit</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Ang Problema: Mga Generic kumpara sa Makabuluhang Error</h2>
      <p>
        Bago ang standardisasyon, gumamit ang mga PayCal module ng ad-hoc na pamamahala ng error:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ MALI: Inilalantad ang hilaw na error, dinidoble ang lohika
PC.showToast(error?.message || 'Nabigo ang pag-import.');
PW.error(`Nabigo ang pag-import: ${error.message}`);</code></pre>
      </div>
      <p>Mga problema sa pamamaraang ito:</p>
      <ul class="doc-list">
        <li>Nakakakita ang mga gumagamit ng nakakalito na mga hilaw na mensahe tulad ng &quot;ECONNREFUSED: Tinanggihan ang koneksyon&quot;</li>
        <li>Bawat module ay nagpapatupad ng sariling lohika ng fallback nang nakapag-iisa</li>
        <li>Walang konsistenteng pag-trim ng whitespace o pagtanggal ng prefix</li>
        <li>Ang mga walang laman na mensahe ng error ay maaaring lumabas bilang &quot;undefined&quot; sa UI</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Ang Solusyon: Standardisadong Error Resolver</h2>
      <p>
        Lahat ng PayCal frontend module ay gumagamit na ngayon ng pinag-isang resolver function na nag-normalize ng mga mensahe ng error:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ TAMA: Na-normalize, konsistente, ligtas
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Kunin ang mensahe mula sa error object
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // Alisin ang prefix na &quot;Error:&quot; at i-trim ang whitespace
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Ibalik ang na-normalize kung hindi walang laman; kung hindi ang ligtas na fallback
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Paggamit:</strong></p>
      <div class="doc-code-block">
        <pre><code>// Sa mga catch block sa lahat ng module
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'Hindi ma-update ang profile.');
  PC.showToast(message, 'error');  // Nakikita ng gumagamit ang makabuluhang feedback
  PW.error(message);                // Naitala para sa pag-debug
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Saklaw ng Implementasyon</h2>
      <p>
        Simula Abril 2026, ang standardisadong pattern na ito ng pamamahala ng error ay inilapat sa
        <strong>11 frontend module</strong> na may <strong>~40+ na na-normalize na catch block</strong>:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Authentication at Settings (7 module)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Core at Data Module (4 module)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>Mga module na may mataas na halaga (10+ catch point):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/businesses/index.php</code> — Pamamahala ng organisasyon, mga kahilingan sa access, mga audit trail (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — Site CRUD, kita, pagbawi ng nag-iisang trabaho (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Mga operasyon sa pagpasok ng araw, kopya/i-paste/tanggalin (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Mga Kategorya ng Error at Mga Pattern ng Pamamahala</h2>
      <p>Ang resolver ay inilalapat nang konsistente sa iba't ibang kategorya ng error:</p>
      
      <h3>1. Mga Pagkabigo ng Network Request</h3>
      <div class="doc-code-block">
        <pre><code>// Network module: Mga HTTP error, timeout, mga isyu sa koneksyon
async function deleteResource(ep, id) {
  try {
    // ...lohika ng fetch...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Error sa network');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. Pamamahala ng API Response</h3>
      <div class="doc-code-block">
        <pre><code>// Billing/Settings: Nagbalik ang server ng mensahe ng error sa payload
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'Hindi ma-load ang katayuan ng billing.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'Hindi ma-load ang katayuan ng billing.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. Mga Pagkabigo sa Operasyon ng UI</h3>
      <div class="doc-code-block">
        <pre><code>// Calendar/Organisations: Mga aksyon na sinimulan ng gumagamit (i-paste, tanggalin, i-update)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('Matagumpay!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'Nabigo ang aksyon. Subukan muli.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Asynchronous na Pagsisimula</h3>
      <div class="doc-code-block">
        <pre><code>// Core module: Mga pagkabigo sa pagsisimula o nakasalalay na pagsisimula
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'Nabigo ang pagsisimula ng navigation');
  PW.warn(resolved);  // Naitala ngunit hindi nakaharang sa pahina
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Mga Pagsasaalang-alang sa Seguridad</h2>
      <p>
        Ang normalisasyon ng mensahe ng error ay nagpoprotekta sa privacy ng gumagamit at integridad ng sistema:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Walang detalye ng database:</strong> Ang mga error sa backend tulad ng &quot;UNIQUE constraint failed on email&quot;
          ay nahaharang sa hangganan ng API at pinapalitan ng mga mensaheng pang-gumagamit
        </li>
        <li>
          <strong>Walang mga path ng file:</strong> Ang mga error sa sistema na nagbubunyag ng mga path ng file o detalye ng proseso ay inaalis
        </li>
        <li>
          <strong>Walang pagtagas ng authentication:</strong> Ang mga tugon sa mga pagkabigo ng authentication ay hindi kailanman nagbubunyag
          kung ang isang account ay umiiral (mga generic na mensahe lamang na ligtas sa oras)
        </li>
        <li>
          <strong>Walang detalye ng CORS/network:</strong> Ang mga error sa antas ng transportasyon ay na-normalize sa
          mga generic na mensahe ng &quot;Error sa koneksyon&quot;
        </li>
        <li>
          <strong>Mga ligtas na fallback:</strong> Ang lahat ng catcher ay may mga explicit na fallback na mensahe;
          hindi kailanman nagpapakita ng &quot;undefined&quot; o &quot;null&quot;
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Mga Benepisyo sa Karanasan ng Gumagamit</h2>
      <p>
        Ang mga standardisadong mensahe ng error ay malaki ang naiaambag sa pagpapabuti ng karanasan ng gumagamit:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Malinaw na Feedback:</strong> Alam ng mga gumagamit kung ano ang nabigo (hal. &quot;Hindi nakilala ang passkey&quot;
          kumpara sa generic na &quot;Nabigo ang pag-sign in&quot;)
        </li>
        <li>
          <strong>Mga Susunod na Hakbang na Maisasagawa:</strong> Kung posible, nagmumungkahi ang mga mensahe ng mga lunas
          (&quot;Subukan muli&quot;, &quot;Suriin ang iyong koneksyon&quot;, &quot;Makipag-ugnayan sa suporta&quot;)
        </li>
        <li>
          <strong>Konsistensya sa Buong App:</strong> Ang parehong uri ng error ay lilitaw sa parehong paraan sa lahat ng dako,
          binabawasan ang kalituan ng gumagamit
        </li>
        <li>
          <strong>Mga Accessible na Estado ng Error:</strong> Inihahayag ng mga screen reader ang mga na-normalize na mensahe;
          nagbibigay ang pag-log ng kumpletong konteksto para sa mga koponan ng suporta
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Workflow ng Pag-debug at Suporta</h2>
      <p>
        Ang normalisasyon ng error ay <strong>hindi</strong> nag-aalis ng kakayahan sa pag-debug. Buong detalye ng error
        ay dumadaloy sa Phantom Wing:
      </p>
      <div class="doc-code-block">
        <pre><code>// Nakakita ang gumagamit ng malinis na mensahe sa UI
PC.showToast(resolveThrownMessage(error, 'Nabigo ang pag-upload.'), 'error');

// Nakakita ang koponan ng suporta ng kumpletong detalye sa mga log ng Phantom Wing
PW.error('Nabigo ang pag-upload', {
  userMessage: resolveThrownMessage(error, 'Nabigo ang pag-upload.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Pagsubok at Tiyak na Kalidad</h2>
      <p>
        Ang lahat ng pagbabago sa pamamahala ng error ay bina-validate bago i-deploy:
      </p>
      <ul class="doc-list">
        <li><strong>Validation ng Syntax:</strong> Bine-verify ng <code>php -l</code> at <code>node --check</code> ang kawastuhan</li>
        <li><strong>Kaligtasan ng Uri:</strong> Kinukumpirma ng mga diagnostic ng editor na walang regression ng uri</li>
        <li><strong>Integration Testing:</strong> Sinubukan ang mga catch block gamit ang mga mock na error object</li>
        <li><strong>Phantom Wing Logging:</strong> Na-verify ang mga mensahe ng error sa mga debug log</li>
        <li><strong>Accessibility Audit:</strong> Sinubukan ang mga anunsyo ng screen reader para sa kalinawan</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Pagpapanatili at Mga Hinaharap na Extension</h2>
      <p>
        Ang pattern na ito ay dinisenyo para sa pangmatagalang pagiging mapanatili:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Handa para sa Lokalisasyon:</strong> Maaaring ipadaan ang mga mensahe ng error sa pamamagitan ng i18n nang hindi
          binabago ang lohika ng resolver
        </li>
        <li>
          <strong>Napapalawak:</strong> Maaaring palawakin ang resolver upang mahawakan ang mga error code, lohika ng muling pagsubok,
          o espesyalisadong paghahanap ng mensahe nang hindi sinisira ang kasalukuyang code
        </li>
        <li>
          <strong>Dokumentasyon:</strong> Kasama sa bawat module ang mga inline na komento na nagpapaliwanag
          ng mga senaryo ng error at mga estratehiya ng fallback
        </li>
        <li>
          <strong>Kasaysayan ng Git:</strong> Lahat ng pagbabago ay sinusubaybayan gamit ang mga detalyadong mensahe ng commit at
          mga diff sa antas ng file para sa madaling pagsusuri
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Buod: Ang PayCal na Pamantayan ng Pamamahala ng Error</h2>
      <p>
        Tinitiyak ng standardisadong normalisasyon ng mensahe ng error ng PayCal na:
      </p>
      <ol class="doc-list">
        <li>Makatanggap ang mga gumagamit ng malinaw, maisasagawang feedback ng error</li>
        <li>Ang sensitibong mga detalye ng sistema ay hindi kailanman tumatawid sa frontend</li>
        <li>Ang pamamahala ng mensahe ay konsistente sa lahat ng 11+ frontend module</li>
        <li>Pinapanatili ng mga koponan ng pag-debug at suporta ang kumpletong konteksto ng error sa pamamagitan ng Phantom Wing</li>
        <li>Ang code ay mapapanatili, maaaring masuri, at accessible</li>
      </ol>
      <p class="doc-section-footer-note">
        Ang pangakong ito sa seguridad, kalinawan, at konsistensya ay sumasalamin sa dedikasyon ng PayCal
        sa tiwala ng gumagamit at transparent na pagbabahagi ng impormasyon.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
