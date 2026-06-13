<?php
/**
 * Public Transparency: Opt-in Diagnostics & Phantom Wing
 *
 * PURPOSE:
 * Explain how PayCal's optional diagnostics system works, what data it collects
 * (and what it never collects), who controls it, and how it helps troubleshoot
 * problems without compromising privacy.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Opt-in Diagnostics &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Ang PayCal ay may kasamang opsyonal na diagnostic layer na ikaw ang namamahala. Narito
      ang eksaktong kinukuha nito, kung ano ang nananatili sa iyong device, at kung paano ito
      ginagamit.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Pangkalahatang-ideya</h2>
      <p>
        Ang PayCal ay may built-in na diagnostic layer na tinatawag na <strong>Phantom Wing</strong>.
        Sa default, ito ay halos ganap na tahimik — kinukuha lamang nito ang mga malubhang
        hindi naprosesong error at hindi kailanman nagpapadala ng kahit ano nang walang iyong
        malinaw na pag-opt-in.
      </p>
      <p>
        Kung may naranasan kang problema at nais mong ibahagi ang karagdagang konteksto sa
        suporta, maaari mong i-enable ang karagdagang diagnostics sa
        <a href="/settings/">Mga Setting → Pag-debug (Opsyonal)</a>.
        Ang bawat setting ay independyente; maaari mong i-on lamang ang may kaugnayan.
        Lahat ng tatlo ay naka-default sa <strong>Off</strong>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Ang tatlong opt-in na kontrol</h2>
      <p>
        Ang bawat kontrol ay nasa panel na <strong>Pag-debug (Opsyonal)</strong> sa ibaba ng
        iyong pahina ng Mga Setting. Dinisenyo ang mga ito para sa pag-troubleshoot lamang —
        ang pag-on sa mga ito ay maaaring bahagyang magpabagal ng mga interaksyon sa pahina
        dahil may karagdagang gawain na nangyayari sa browser.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Setting</th>
            <th>Ang pinagagana nito</th>
            <th>Sino ang nakakita</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Mga Console Message</strong></td>
            <td>
              Naglalabas ng mga babala, informational na log, at performance marker sa developer
              console ng iyong browser. Kapaki-pakinabang para sa self-diagnosis — buksan ang
              DevTools at hanapin ang mga mensahe na may prefix na <code>[PayCal]</code> o
              emoji marker.
            </td>
            <td>Ikaw lamang (ang iyong browser console, hindi kailanman ipinadala)</td>
          </tr>
          <tr>
            <td><strong>Detalyadong Diagnostics</strong></td>
            <td>
              Nag-e-enable ng hakbang-hakbang na internal na event logging. Kinikuha ng
              Phantom Wing ang buong lifecycle ng mga operasyon (mga calendar load, form
              submission, session event) sa isang in-memory log na kasama sa anumang
              ulat ng suporta na pinipili mong ibahagi.
            </td>
            <td>Ikaw lamang, maliban kung magbabahagi ka ng ulat ng suporta</td>
          </tr>
          <tr>
            <td><strong>Mga Network Insight</strong></td>
            <td>
              Naglo-log ng API request timing — gaano katagal ang bawat server round-trip,
              mga laki ng tugon, at kung nailapat ang batching o caching. Tumutulong sa
              pag-diagnose ng kabagalan sa mga partikular na operasyon.
            </td>
            <td>Ikaw lamang (ang iyong browser console, hindi kailanman ipinadala)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Ano ang ginagawa ng Phantom Wing bilang default</h2>
      <p>
        Kahit naka-off ang lahat ng tatlong kontrol, nagpapatakbo ang Phantom Wing ng magaan na
        baseline monitor na kumukuha lamang ng malubhang mga kabiguan:
      </p>
      <ul class="doc-list">
        <li>Mga hindi nahuling JavaScript exception (<code>window.onerror</code>)</li>
        <li>Mga hindi naprosesong promise rejection</li>
        <li>Mga Fetch call na nabigo dahil sa network error (hindi HTTP error — ang mga iyon ay hinahawakan bawat feature)</li>
      </ul>
      <p>
        Ang baseline data na ito ay nananatili sa memorya at hindi kailanman ipinapadala kahit
        saan. Ipinapakita ito sa isang segundong buod sa browser console kapag nag-load ang
        pahina upang mabilis mong makita kung may nangyaring mali, pagkatapos ay tinanggal.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Baseline output when all clear (console, diagnostics off):
[PHANTOM WING] All clear - no errors or warnings detected.

// Baseline output when issues exist:
[PHANTOM WING] Error Summary
Total issues: 2 across 2 grouped location(s).
WARN 1: FormSubmit timed out after 8000ms
ERROR 1: Uncaught TypeError in calendar renderer</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Phantom Wing &amp; Telemetry</h2>
      <p>
        Ang Phantom Wing ay may magaan na telemetry channel na ginagamit upang sukatin ang
        pagiging maaasahan ng feature sa pinagsama-samang paraan — halimbawa, ang pagtuklas
        kung ang isang partikular na operasyon ay nabibigo sa hindi karaniwang rate sa buong
        platform.
      </p>
      <h3>Ano ang ipinapadala ng telemetry</h3>
      <ul class="doc-list">
        <li>Mga anonymous na bilang ng event na nakagrupo bawat oras (hal. <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Kategorya at uri ng error — hindi kailanman ang buong mensahe ng error o stack trace</li>
        <li>Walang identifier ng user, walang session token, walang IP address</li>
      </ul>
      <h3>Ano ang hindi kailanman ipinapadala ng telemetry</h3>
      <ul class="doc-list">
        <li>Ang iyong pangalan, email, o anumang detalye ng account</li>
        <li>Kita, pay period, o financial na data</li>
        <li>Mga buong mensahe ng error o stack trace</li>
        <li>Mga URL path o query string</li>
        <li>Mga keystroke o halaga ng form field</li>
      </ul>
      <h3>Rate limiting &amp; back-off</h3>
      <p>
        Ang mga telemetry submission ay rate-limited sa server-side bawat user bawat minuto.
        Kung lalampas ang iyong client sa threshold, ang server ay tahimik na nagpapatunay at
        itinapon ang labis — walang naiimbak. Naglalapat din ang client ng exponential back-off:
        pagkatapos ng dalawang magkakasunod na server-side failure, awtomatiko nitong hindi
        pinagana ang telemetry submission sa loob ng sampung minuto.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Telemetry payload shape (no personal data):
{
  "type": "pw.performance.metrics",
  "fields": {
    "count": 1,
    "bucket_hour": 2026030914,
    "flush_reason": "timer"
  }
}</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Pag-redact ng Data</h2>
      <p>
        Bago ang anumang halaga ay maiimbak sa memorya o maipadala sa pamamagitan ng telemetry,
        naglalapat ang Phantom Wing ng awtomatikong redaction pass. Ang mga halagang tumutugma
        sa mga kilalang sensitibong pattern ay pinapalitan ng <code>[REDACTED]</code>:
      </p>
      <ul class="doc-list">
        <li>Mga email address</li>
        <li>Mga Bearer token at halaga ng authorization header</li>
        <li>Mga CSRF token</li>
        <li>Mga string na mukhang mga cryptographic key o base64-encoded blob na lampas sa minimum na haba</li>
      </ul>
      <p>
        Ang redaction ay gumagana sa lahat ng argument na ipinasa sa mga na-intercept na console
        method at lahat ng telemetry field value bago ang pagpapahintulot. Hindi ito maaaring
        iwasan sa pamamagitan ng pag-enable ng mga diagnostic setting.
      </p>
    </section>

    <section class="doc-section">
      <h2>Mga Scope Guard: Mga pahinang pinipigilan ang diagnostics</h2>
      <p>
        Ang telemetry submission ay ganap na pinipigilan sa mga pahina ng authentication
        (<code>/auth/</code>). Ibig sabihin nito, kahit naka-on ang Mga Network Insight, walang
        telemetry na ipinapadala habang nasa sign-in, sign-up, o recovery flow ka. Ito ay isang
        defense-in-depth na hakbain upang maiwasan ang anumang posibilidad na ang data na
        kalapit ng kredensyal ay lumabas sa mga diagnostic channel.
      </p>
    </section>

    <section class="doc-section">
      <h2>Ang Iyong Kontrol</h2>
      <p>
        Ang lahat ng tatlong diagnostic setting ay naka-imbak bilang mga kagustuhan ng account,
        hindi bilang mga browser cookie. Sinusundan nila ang iyong account sa lahat ng device at
        session at naka-default sa <strong>Off</strong> para sa bawat account — kasama ang mga
        bagong account. Maaari mong baguhin ang mga ito anumang oras sa
        <a href="/settings/">Mga Setting → Pag-debug (Opsyonal)</a>.
      </p>
      <p>
        Ang pag-off ng isang setting ay agad na magkakabisa sa susunod na pag-load ng pahina.
        Walang diagnostic data na pinanatili sa pagitan ng mga session: ang in-memory log ng
        Phantom Wing ay nililinis kapag lumayo ka o isinara ang tab.
      </p>
    </section>

    <section class="doc-section">
      <h2>Buod</h2>
      <ol class="doc-list">
        <li>Lahat ng tatlong debug control ay naka-default sa <strong>Off</strong> at dapat na malinaw na i-enable mo</li>
        <li>Ang Mga Console Message at Mga Network Insight ay hindi kailanman umaalis sa iyong device</li>
        <li>Ang Detalyadong Diagnostics ay nananatili sa memorya at ibinabahagi lamang kung pipiliin mong ibahagi ang isang ulat ng suporta</li>
        <li>Ang telemetry ay nagpapadala lamang ng mga anonymous, pinagsama-samang bilang ng event — zero personal na data</li>
        <li>Lahat ng halaga ay na-redact bago ang pag-iimbak o pagpapadala, anuman ang mga diagnostic setting</li>
        <li>Ang telemetry ay ganap na pinipigilan sa lahat ng pahina ng authentication</li>
        <li>Ang rate limiting at awtomatikong client back-off ay pumipigil sa anumang aksidenteng labis na pag-uulat</li>
      </ol>
      <p class="doc-section-footer-note">
        Ang Phantom Wing ay dinisenyo upang maaari mong iwan nang hindi pinagana ang lahat ng
        diagnostics nang walang katiyakan. Ang mga opt-in control ay umiiral upang bigyan kayo
        ng support team ng isang shared na wika kapag may nangyaring mali — hindi para mangolekta
        ng data bilang default.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
