<?php
/**
 * Public Transparency: Network Capabilities
 *
 * PURPOSE: Publish PayCal's transport/security header baseline,
 *          protocol behavior, and network posture in plain language.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_NETWORK_CAPABILITIES_PAGE_TITLE',
  'TRANSPARENCY_NETWORK_CAPABILITIES_DECK',
  'TRANSPARENCY_NETWORK_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_NETWORK_EXECUTIVE_SUMMARY_TITLE',
  'TRANSPARENCY_NETWORK_PROTOCOL_ROUTING_TITLE',
  'TRANSPARENCY_NETWORK_TRANSPORT_CONTROLS_TITLE',
  'TRANSPARENCY_NETWORK_BROWSER_HEADERS_TITLE',
  'TRANSPARENCY_NETWORK_QUIC_WORKLOADS_TITLE',
  'TRANSPARENCY_NETWORK_SCOPE_NOTES_TITLE',
  'TRANSPARENCY_NETWORK_TABLE_CONTROL',
  'TRANSPARENCY_NETWORK_TABLE_OBSERVED_VALUE',
  'TRANSPARENCY_NETWORK_TABLE_PURPOSE',
  'TRANSPARENCY_NETWORK_HEADERS_TABLE_HEADER',
  'TRANSPARENCY_NETWORK_HEADERS_TABLE_SECURITY_EFFECT',
  'TRANSPARENCY_NETWORK_TRANSPORT_TABLE_ARIA',
  'TRANSPARENCY_NETWORK_HEADERS_TABLE_ARIA',
  'TRANSPARENCY_NETWORK_USE_CASE_ARIA',
  'TRANSPARENCY_NETWORK_USE_CASE_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_NETWORK_CAPABILITIES_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_NETWORK_CAPABILITIES_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_NETWORK_CAPABILITIES_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_NETWORK_CAPABILITIES_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_NETWORK_CAPABILITIES_DECK']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_NETWORK_VERIFICATION_METADATA_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong>Route:</strong> <code>/transparency/network-capabilities/</code></li>
        <li><strong>Last verified:</strong> <time datetime="2026-03-29">2026-03-29</time></li>
        <li><strong>Verification targets:</strong> <code>paycal.app</code>, <code>www.paycal.app</code>, <code>dev.paycal.app</code></li>
        <li><strong>Capture method:</strong> HTTP response-header inspection and protocol negotiation probes.</li>
        <li><strong>Observed entry behavior:</strong> HTTPS entrypoints redirect to auth routes with security headers applied on redirect responses.</li>
      </ul>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_NETWORK_EXECUTIVE_SUMMARY_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li>All public entrypoints are HTTPS and redirect to canonical auth routes.</li>
        <li>HTTP/3 is advertised via <code>Alt-Svc</code> and negotiated automatically by compatible clients.</li>
        <li>HSTS preload policy is enabled to enforce HTTPS at browser level.</li>
        <li>Cross-origin isolation controls (COOP, COEP, CORP) are active.</li>
        <li>Browser-hardening headers include CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, and Permissions-Policy.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_NETWORK_PROTOCOL_ROUTING_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong>Primary protocol baseline:</strong> HTTP/2 observed on initial requests.</li>
        <li><strong>HTTP/3 advertisement:</strong> <code>alt-svc: h3=":443"; ma=86400</code>.</li>
        <li><strong>Fallback behavior:</strong> clients that cannot use QUIC continue on HTTP/2 or HTTP/1.1 without route-level behavior change.</li>
        <li><strong>Canonical redirect behavior:</strong> root and non-auth entrypoints redirect to auth paths (for example, <code>https://paycal.app/</code> to <code>https://www.paycal.app/auth/</code>).</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_NETWORK_TRANSPORT_CONTROLS_TITLE']; ?></h2>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_NETWORK_TRANSPORT_TABLE_ARIA']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_NETWORK_TABLE_CONTROL']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_NETWORK_TABLE_OBSERVED_VALUE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_NETWORK_TABLE_PURPOSE']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>HSTS</td>
            <td><code>strict-transport-security: max-age=31536000; includeSubDomains; preload</code></td>
            <td>Forces HTTPS and supports preload inclusion to reduce downgrade risk.</td>
          </tr>
          <tr>
            <td>HTTP/3 Advertisement</td>
            <td><code>alt-svc: h3=":443"; ma=86400</code></td>
            <td>Enables client upgrade to QUIC/HTTP/3 while preserving backward compatibility.</td>
          </tr>
          <tr>
            <td>Server Signature</td>
            <td><code>server: nginx</code></td>
            <td>Documents observed edge server family at verification time.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_NETWORK_BROWSER_HEADERS_TITLE']; ?></h2>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_NETWORK_HEADERS_TABLE_ARIA']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_NETWORK_HEADERS_TABLE_HEADER']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_NETWORK_TABLE_OBSERVED_VALUE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_NETWORK_HEADERS_TABLE_SECURITY_EFFECT']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>CSP</td>
            <td><code>content-security-policy: default-src 'self' https: data: blob:; object-src 'none'; frame-ancestors 'none'; base-uri 'self'</code></td>
            <td>Restricts script/resource origins, blocks plugin objects, blocks framing, and constrains base URI manipulation.</td>
          </tr>
          <tr>
            <td>COOP</td>
            <td><code>cross-origin-opener-policy: same-origin</code></td>
            <td>Places pages in an isolated browsing context group to reduce cross-window attack surface.</td>
          </tr>
          <tr>
            <td>COEP</td>
            <td><code>cross-origin-embedder-policy: require-corp</code></td>
            <td>Requires embeddable resources to be explicitly allowed, supporting stronger isolation boundaries.</td>
          </tr>
          <tr>
            <td>CORP</td>
            <td><code>cross-origin-resource-policy: same-site</code></td>
            <td>Restricts resource loading across site boundaries.</td>
          </tr>
          <tr>
            <td>X-Content-Type-Options</td>
            <td><code>x-content-type-options: nosniff</code></td>
            <td>Prevents MIME-type sniffing for safer resource interpretation.</td>
          </tr>
          <tr>
            <td>X-Frame-Options</td>
            <td><code>x-frame-options: DENY</code></td>
            <td>Blocks framing to reduce clickjacking risk.</td>
          </tr>
          <tr>
            <td>Referrer-Policy</td>
            <td><code>referrer-policy: strict-origin-when-cross-origin</code></td>
            <td>Limits referrer data sent on cross-origin navigations.</td>
          </tr>
          <tr>
            <td>Permissions-Policy</td>
            <td><code>permissions-policy: accelerometer=(), camera=(), microphone=(), geolocation=(), usb=(), unload=()</code></td>
            <td>Disables high-risk browser capabilities by default unless explicitly granted later.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_NETWORK_QUIC_WORKLOADS_TITLE']; ?></h2>
      <p>PayCal uses increasingly API-oriented page flows (for example, lazy-loaded earnings sections). In this request pattern, reduced connection and transport overhead improves aggregate responsiveness.</p>
      <div class="subject-example-cutout" role="note" aria-label="<?php echo $i18n['TRANSPARENCY_NETWORK_USE_CASE_ARIA']; ?>">
        <h3><?php echo $i18n['TRANSPARENCY_NETWORK_USE_CASE_TITLE']; ?></h3>
        <p>Before promoting a release, operations can run a simple header probe on production and dev domains and compare HSTS, CSP, COOP/COEP/CORP, and Alt-Svc values against this published baseline to catch misconfigured edge policy early.</p>
      </div>
      <ul class="doc-fact-list">
        <li>QUIC support is active through HTTP/3 advertisement over port 443.</li>
        <li>Browsers upgrade automatically when QUIC is available and healthy.</li>
        <li>If QUIC is unavailable on a client or network path, requests proceed on HTTP/2 fallback without feature loss.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_NETWORK_SCOPE_NOTES_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li>This article documents verified edge-network behavior and response headers observed at publication time.</li>
        <li>Header sets can vary by route class (redirect, auth, app, API, and error responses).</li>
        <li>Values above reflect baseline controls seen on production and dev public entrypoints.</li>
      </ul>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
