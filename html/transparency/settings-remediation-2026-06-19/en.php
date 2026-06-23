<?php
/**
 * Public Transparency: June 2026 Remediation Report
 *
 * PURPOSE:
 * Explain the full June 19 remediation effort: protected work-data boundaries,
 * compatibility/shim cleanup, Redis/data audits, crypto/plaintext migration
 * readiness, security findings, pay-period correctness, and settings UI fixes.
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
$pageTitle = 'June 2026 Remediation Report - [PayCal]';
$pageLabel = 'June 2026 Remediation Report';
$pageMetaDescription = 'PayCal completed a June 2026 remediation covering protected work-data boundaries, compatibility shims, Redis data drift, crypto/plaintext migration readiness, pay-period DST correctness, security findings, and settings UI controls.';
$pageMetaDescriptionLong = 'PayCal completed a broad June 2026 remediation program that investigated protected business work-data access, stale compatibility shims, Redis data drift, crypto and plaintext work-entry migration readiness, pay-period DST correctness, security findings, and settings navigation and appearance controls.';
$pageSocialTitle = 'June 2026 Remediation Report';
$pageOgDescription = 'A public summary of PayCal\'s June 2026 remediation program: what we investigated, the bugs found, what we fixed, what remains intentionally guarded, and the regression suites now protecting the system.';
$pageTwitterTitle = 'June 2026 Remediation Report';
$pageTwitterDescription = 'How PayCal remediated protected data boundaries, compatibility drift, pay-period DST behavior, security findings, and settings controls in June 2026.';
$pageDcTitle = 'June 2026 Remediation Report';
$pageDcDescription = $pageMetaDescription;
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">June 2026 Remediation</span>
  </nav>

  <header class="doc-article-header">
    <h1>June 2026 Remediation Report</h1>
    <p class="deck">
      On June 19, 2026 we completed a broad remediation pass that started as a
      protected work-data and compatibility audit, then expanded into Redis data
      repair, crypto/plaintext migration readiness, security hardening, pay-period
      correctness, and settings UX guardrails. This was not only a settings
      cleanup. The settings work was one visible part of a wider investigation.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-19">2026-06-19</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; Release target: <code>v1.059.000</code></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Executive Summary</h2>
      <table class="doc-table" aria-label="June remediation summary">
        <tbody>
          <tr>
            <td><strong>Primary concern</strong></td>
            <td>Protected work data and old compatibility behavior could not be trusted by assumption. We needed to prove current boundaries, migrate safe drift, and leave guardrails where live data still requires compatibility.</td>
          </tr>
          <tr>
            <td><strong>Major bugs found</strong></td>
            <td>A DST-sensitive biweekly pay-period calculation could fail to advance correctly, stale compatibility paths kept old routes and concepts alive, Redis connection indexes had drift, and several security review findings needed closure.</td>
          </tr>
          <tr>
            <td><strong>Major fixes</strong></td>
            <td>Protected business work reads now use a canonical access gate, stale shims and aliases were removed, Redis relationship drift was repaired, crypto/plaintext audits were added, pay-period DST navigation was fixed, security findings were closed, and settings controls were rebuilt with tests.</td>
          </tr>
          <tr>
            <td><strong>What remains intentionally guarded</strong></td>
            <td>Plaintext/non-encrypted work-entry read compatibility remains because real-user rows still require user-session encrypted rewrite. Work-entry short-alias read compatibility also remains pending encrypted-payload fixture and sample audits.</td>
          </tr>
          <tr>
            <td><strong>Release verification</strong></td>
            <td>Private full PHPUnit passed: 2,318 tests, 19,222 assertions, 29 skipped. Public quick gate passed: 1,311 tests, 7,698 assertions. Private and public PHPStan Level 9 gates were clean.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Why We Investigated</h2>
      <p>
        The remediation started with a question about whether PayCal's protected
        business work-data lifecycle was complete enough: identity, membership,
        consent, access, encrypted envelope state, exports, revocation, caching,
        and audit evidence all needed to line up. The investigation then widened
        because several old compatibility paths, route shims, skipped tests, and
        Redis migration artifacts made it harder to prove the current product
        model.
      </p>
      <p>
        The working rule was direct: old compatibility should either be proven
        still necessary and guarded, or proven unused and removed. For sensitive
        work data, assumptions were not enough.
      </p>
    </section>

    <section class="doc-section">
      <h2>Bugs And Risks Found</h2>
      <ul class="doc-list">
        <li>Protected business member work rows could be approached through multiple report, summary, warmer, and export paths unless one canonical access gate was enforced.</li>
        <li>Generic PDF/XLSX export endpoints accepted client-supplied rows. That remained acceptable for personal exports, but not for business/member protected exports.</li>
        <li>Revocation and actor-scoped summaries needed stronger cache behavior so stale protected data could not survive consent, membership, or wrap changes.</li>
        <li>Biweekly pay-period navigation used timestamp seconds divided by 86,400. Across a spring DST boundary, that can miscount calendar days and cause the next period to fail to advance correctly.</li>
        <li>A disabled earnings lazy-render regression test hid the pay-period issue because the render path could stall around the DST-sensitive period transition.</li>
        <li>Generated docblock TODOs drowned out real TODOs, making the codebase appear less trustworthy and making real remediation targets harder to see.</li>
        <li>A runtime compatibility branch treated bare <code>work.write</code> as broader business mutation access than the explicit self/org scope model should allow.</li>
        <li>Old routes and shims such as legacy earnings/profile/settings-hash paths kept replaced concepts alive in tests, docs, and browser code.</li>
        <li>Redis connection indexes had drift and old relationship/metaphor data that needed migration and verification.</li>
        <li>Crypto/passkey and plaintext work-entry compatibility were real migration areas, not cleanup targets that could be deleted casually.</li>
        <li>Security review items identified IP spoofing risk in request-IP handling, non-constant-time comparisons, CORS origin logic, dev security flag misuse, hash comparison consistency, MD5 rate-limit keys, and verbose admin test output.</li>
        <li>Settings sidebar hover, icon grouping, accent controls, notification positioning, and settings nav contrast had visible UX regressions and weak control feedback.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Protected Work Data</h2>
      <p>
        The protected-data remediation closed the most important boundary first:
        protected business member work rows may only originate from the canonical
        protected access gate before they can be read, reported, exported,
        cached, or audited.
      </p>
      <ul class="doc-list">
        <li>Added and enforced <code>BusinessProtectedDataAccess</code> for actor authority, active target membership, consent, active wrap state, envelope metadata, and business site visibility.</li>
        <li>Routed member reports, financial summaries, workspace warmers, business reports, daily member JSON, and member XLSX/PDF export paths through the protected gate.</li>
        <li>Locked generic PDF/XLSX endpoints to personal current-user payloads and added negative tests for forged business/member payloads.</li>
        <li>Moved protected business XLSX/PDF export to server-side rebuilds from authorized rows. Browser code now sends export intent, not trusted rows.</li>
        <li>Expanded audit events for requested, denied, started, completed, and failed protected read/export outcomes.</li>
        <li>Added revocation tests proving stale reports, exports, summaries, business reports, and caches are denied or purged after access changes.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Pay Period DST Bug</h2>
      <p>
        One real product bug found during the cleanup was in biweekly pay-period
        navigation. The old calculation converted timestamp seconds into days by
        dividing by <code>86400</code>. That looks reasonable until a timezone has
        a daylight-saving transition. A spring-forward period is not a clean
        multiple of 24-hour days, so a date that should have advanced to the next
        biweekly period could be resolved incorrectly.
      </p>
      <p>
        This mattered because pay periods drive earnings grouping, period labels,
        lock boundaries, and lazy earnings rendering. A disabled lazy-render
        regression led us to the root cause: DST-sensitive biweekly navigation
        could return the same period around the America/Edmonton spring boundary.
      </p>
      <ul class="doc-list">
        <li>Fixed <code>PayPeriods::biweeklyStart()</code> to use calendar-day differences instead of timestamp seconds divided by 86,400.</li>
        <li>Added a regression for the spring DST path: 2024-02-26 advances to 2024-03-11, then 2024-03-25 in America/Edmonton.</li>
        <li>Re-enabled the earnings lazy-render regression after fixing the scheduler root cause.</li>
        <li>Kept pay-period tests in the remediation verification set because this bug can affect earnings totals and user trust even when no security boundary is involved.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Compatibility And Shim Cleanup</h2>
      <p>
        We separated harmless historical references from active compatibility
        behavior. Active compatibility that still affected routing, access, or
        data interpretation was either removed or converted into an audited
        migration path.
      </p>
      <table class="doc-table" aria-label="Compatibility remediation results">
        <thead>
          <tr>
            <th>Area</th>
            <th>Outcome</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Generated TODO backlog</td>
            <td>Stopped the docblock helper from generating <code>TODO: Document</code> placeholders and removed the active generated TODO noise from source.</td>
          </tr>
          <tr>
            <td>Legacy route shims</td>
            <td>Removed old route and asset shims for replaced earnings, profile, settings-hash, business JS, and blog article paths where tests no longer needed them.</td>
          </tr>
          <tr>
            <td>API aliases</td>
            <td>Replaced old profile/settings update routes with account-scoped routes and removed unused method aliases/wrappers.</td>
          </tr>
          <tr>
            <td>Business connection scopes</td>
            <td>Removed the branch that treated bare <code>work.write</code> as org-wide work mutation access. Runtime access now requires explicit self/org scope or manager authority.</td>
          </tr>
          <tr>
            <td>Skipped tests</td>
            <td>Added deterministic Argus CLI context seeding and removed the skip from the affected quick-gate unit path.</td>
          </tr>
          <tr>
            <td>Placeholder code</td>
            <td>Deleted unused <code>FPDF.php</code> placeholder code and reconciled the active PHP inventory.</td>
          </tr>
        </tbody>
      </table>
      <h3>Specific paths, aliases, redirects, methods, and classes removed or changed</h3>
      <table class="doc-table" aria-label="Removed compatibility paths and aliases">
        <thead>
          <tr>
            <th>Old behavior</th>
            <th>Current behavior</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>/earnings/</code> compatibility route and smoke-test references</td>
            <td>Removed. Personal earnings and reports use the canonical <code>/reports/</code> surface.</td>
          </tr>
          <tr>
            <td><code>/profile/</code> compatibility route and profile-page references</td>
            <td>Removed. Account profile settings now live under the settings/account surface.</td>
          </tr>
          <tr>
            <td><code>/js/businesses/</code> asset shim</td>
            <td>Removed after canonical asset references were verified.</td>
          </tr>
          <tr>
            <td>Settings legacy hash redirect map, JSON payload, and browser redirect logic</td>
            <td>Removed. Settings navigation uses canonical settings section values directly.</td>
          </tr>
          <tr>
            <td><code>/blog/article/?slug=...</code> redirect entrypoint</td>
            <td>Removed from active routing and accessibility inventory references.</td>
          </tr>
          <tr>
            <td><code>/api/v1/profile/update/</code></td>
            <td>Replaced by <code>/api/v1/account/profile/update/</code>.</td>
          </tr>
          <tr>
            <td><code>profile/settings</code> API route declarations</td>
            <td>Replaced by account-scoped profile settings routes.</td>
          </tr>
          <tr>
            <td>Unused <code>BusinessDiscovery</code> method aliases</td>
            <td>Removed so business discovery code has one current method vocabulary.</td>
          </tr>
          <tr>
            <td>Unused <code>ShadowTalon::init()</code> alias</td>
            <td>Removed in favor of the current initialization method.</td>
          </tr>
          <tr>
            <td>Old Settings account update route aliases</td>
            <td>Removed after account settings callers were moved to canonical routes.</td>
          </tr>
          <tr>
            <td>Internal <code>EmailVerification</code> rate-limit wrapper</td>
            <td>Removed where it duplicated the current rate-limit path.</td>
          </tr>
          <tr>
            <td>Runtime bare <code>work.write</code> business mutation compatibility</td>
            <td>Removed. The connection audit reports bare-scope drift, <code>--fix</code> migrates it, and runtime grants require explicit self/business scope or manager authority.</td>
          </tr>
          <tr>
            <td>Generated <code>TODO: Document...</code> comments</td>
            <td>Stopped at the generator and removed from active source so real TODO/FIXME scans are meaningful.</td>
          </tr>
          <tr>
            <td>Unused <code>html/src/Domain/FPDF.php</code> placeholder class</td>
            <td>Deleted and removed from the active accessibility/PHP inventory.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Redis And Data Drift</h2>
      <p>
        We used report-only Redis audits before applying migration fixes. Where
        drift was proven safe to repair, we created a checkpoint, ran the fix,
        then reran the audit in report-only mode.
      </p>
      <ul class="doc-list">
        <li>Created a Redis checkpoint before mutation: <code>paycal-redis-20260619-091729.rdb</code>, SHA-256 <code>baff0c94361b57ef67a153b242669c537165a9b576a1cfb4953b10e95fa41215</code>.</li>
        <li>Migrated old relationship/metaphor Redis keys and values: 4,288 key migrations and 599 value updates, with 0 conflicts and 0 errors.</li>
        <li>Repaired connection index drift across 1,698 connections and 1,447 businesses. The fixer repaired 378 drifted records and verified post-run drift at 0 with no owner invariant violations.</li>
        <li>Extended <code>scripts/connections-audit.php</code> so report-only mode identifies bare <code>work.write</code> drift and <code>--fix</code> migrates records to explicit <code>work.scope.self</code>.</li>
        <li>Replaced the stale SQL-era Redis field migration helper with a Redis-native dry-run/execute/verify flow for <code>text_sizing -&gt; text</code> and <code>density -&gt; spacing</code>; verification found 0 remaining legacy fields.</li>
        <li>Confirmed old no-colon email index keys were absent, then removed that compatibility path.</li>
        <li>Confirmed <code>theme_mode</code> settings fields were absent, then removed that fallback.</li>
        <li>Confirmed legacy fixed-TTL lock-boundary keys were absent, then removed that cleanup branch.</li>
        <li>Marked 98 seeded <code>@paycal.app</code> fake personas so plaintext work-entry audits can ignore them by default while still reporting them with <code>--include-ignored</code>.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Crypto And Plaintext Work Entries</h2>
      <p>
        The crypto/plaintext review produced an important decision: not every
        compatibility path should be removed just because it is old. Some protect
        real data that still needs user-session context to migrate safely.
      </p>
      <ul class="doc-list">
        <li>Added crypto compatibility telemetry for unwrap source selection and outcomes.</li>
        <li>Added <code>scripts/paycal crypto:compat:audit --json</code> to report wrapper and fallback usage.</li>
        <li>Added <code>scripts/paycal work:plaintext:audit --json</code> to report plaintext-only rows, invalid encrypted blobs, fake-user rows, and rows requiring user-session rewrite.</li>
        <li>Retired the old plaintext migration execution path so it cannot strip intentional work-entry snapshot fields.</li>
        <li>Backfilled earnings snapshot fields so active rows carry stable reporting facts such as wage, regular/overtime/travel/living-out amounts, gross, and snapshot version.</li>
        <li>Left plaintext/non-encrypted read compatibility in place because the audit still found real-user rows and invalid blobs requiring user-session encrypted rewrite.</li>
        <li>Left work-entry short-alias read compatibility in place because encrypted blobs and fixtures can still contain historical alias-shaped payloads even though Redis active hash fields showed zero short aliases.</li>
      </ul>
      <table class="doc-table" aria-label="Crypto and plaintext audit findings">
        <thead>
          <tr>
            <th>Audit</th>
            <th>Finding</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>crypto:compat:audit</code></td>
            <td>Checked 1,218 users. Current passkey wrappers were present, legacy-only wrapper users were 0, and runtime legacy wrapper blocks were 0.</td>
          </tr>
          <tr>
            <td><code>work:plaintext:audit</code></td>
            <td>Checked 26,676 work entries. The default report ignored seeded fake-user rows and still found real-user rows requiring user-session encrypted rewrite, so plaintext read compatibility stayed guarded.</td>
          </tr>
          <tr>
            <td>Work-entry alias audit</td>
            <td>Scanned 64,447 work keys and found zero active Redis short aliases for <code>d</code>, <code>s</code>, <code>h</code>, <code>r</code>, <code>o</code>, <code>l</code>, <code>t</code>, or <code>w</code>. Alias readers remain for encrypted/historical payload compatibility.</td>
          </tr>
          <tr>
            <td>Earnings snapshot remediation</td>
            <td>Backfilled snapshot fields across 64,447 work entries, backfilled 859 missing gross values, and verified final missing gross and wage counts at 0.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Security Findings Closed</h2>
      <p>
        A separate security finding backlog was closed as part of the same June
        19 effort. These were targeted hardening items, not a known breach.
      </p>
      <ul class="doc-list">
        <li>Active request-IP call paths now use trusted-proxy-aware client IP resolution.</li>
        <li>Invite code comparison uses constant-time comparison.</li>
        <li>CORS validation now evaluates the request origin against an explicit allowlist.</li>
        <li>The development security-disable flag is ignored outside approved dev-like environments.</li>
        <li>Audit-chain hash comparison uses constant-time comparison.</li>
        <li>Rate-limit IP key derivation uses SHA-256 instead of MD5.</li>
        <li>The admin test runner returns summarized output by default instead of raw PHPUnit details.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Settings And Appearance Remediation</h2>
      <p>
        The visible settings work was still important, but it was one part of the
        larger remediation. Settings controls define durable user preferences, so
        they need bounded values, clear visual models, and regression coverage.
      </p>
      <ul class="doc-list">
        <li>Added a persisted sidebar Trigger preference with a 400 ms default, 200 ms minimum, 3,000 ms maximum, and 200 ms step size.</li>
        <li>Cancelled delayed sidebar timers on pointer exit, document leave, and window blur.</li>
        <li>Grouped sidebar navigation into Personal, Business, and bottom Utility actions, with Settings, Help, and Sign Out anchored together.</li>
        <li>Replaced the Accent dropdown with 16 spectrum swatches, hover popovers, shared accent tokens, and a live preview window.</li>
        <li>Renamed Accent color to Accent, Density preset to Density, and Variant to Mode.</li>
        <li>Rebuilt notification position selection as a spatial grid using canonical top/bottom values.</li>
        <li>Fixed settings nav hover, active, and focus states after a global link hover rule produced a poor white hover block in the dark settings header.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Safeguards And Guardrails</h2>
      <table class="doc-table" aria-label="Remediation test and guardrail coverage">
        <thead>
          <tr>
            <th>Suite or guardrail</th>
            <th>What it now protects</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>BusinessProtectedDataAccessTest</code></td>
            <td>Protected rows require active actor authority, target membership, consent, wrap state, envelope metadata, and business visibility.</td>
          </tr>
          <tr>
            <td><code>BusinessMemberReportExportServiceTest</code></td>
            <td>Business member XLSX/PDF exports are rebuilt server-side from authorized protected rows and denied when required access state is missing.</td>
          </tr>
          <tr>
            <td><code>ProtectedWorkDataArchitectureTest</code></td>
            <td>Blocks direct protected row materialization outside the canonical access gate.</td>
          </tr>
          <tr>
            <td><code>PayPeriodsTest</code></td>
            <td>Includes <code>testBiweeklyNavigationAdvancesAcrossSpringDstBoundary</code>, which verifies America/Edmonton biweekly navigation advances from 2024-02-26 to 2024-03-11 and then 2024-03-25 across the spring DST transition.</td>
          </tr>
          <tr>
            <td><code>PayPeriodGeneratorTest</code></td>
            <td>Keeps adjacent pay-period generation behavior in the remediation verification set so calendar-aware period math is exercised beyond the direct DST unit test.</td>
          </tr>
          <tr>
            <td><code>EarningsCalendarParityIntegrationTest</code></td>
            <td>Re-enabled <code>testRenderSectionsLazyDoesNotFatalWhenEncryptedWorkRowOmitsGross</code>, which had exposed the pay-period navigation stall. It now protects lazy earnings rendering with encrypted work rows that omit gross.</td>
          </tr>
          <tr>
            <td><code>BusinessDiscoveryServiceTest</code> and connection audit tests</td>
            <td>Enforce explicit self/org work scopes and deny bare legacy <code>work.write</code> mutation access.</td>
          </tr>
          <tr>
            <td><code>CryptoCompatibilityTelemetryTest</code> and plaintext audit tests</td>
            <td>Measure legacy wrapper and plaintext fallback usage before compatibility removal decisions.</td>
          </tr>
          <tr>
            <td><code>scripts/paycal crypto:compat:audit</code> and <code>scripts/paycal work:plaintext:audit</code></td>
            <td>Provide repeatable operational checks for wrapper compatibility, plaintext-only rows, invalid encrypted blobs, ignored fake personas, and user-session rewrite readiness.</td>
          </tr>
          <tr>
            <td><code>scripts/connections-audit.php</code></td>
            <td>Reports and fixes Redis connection-index drift and bare work-scope drift, then verifies that post-run drift is 0.</td>
          </tr>
          <tr>
            <td>Route, smoke, and accessibility inventory tests</td>
            <td>Assert canonical paths such as <code>/settings/account/</code>, <code>/settings/subscription/</code>, and <code>/reports/</code> while removing references to deleted compatibility routes.</td>
          </tr>
          <tr>
            <td><code>ThemeButtonTokenContractTest</code></td>
            <td>Guards the 16 accent presets, shared accent tokens, generated swatch markup, and live preview markup.</td>
          </tr>
          <tr>
            <td><code>SidebarNavigationContractTest</code></td>
            <td>Guards sidebar group order, bottom utility placement, and delayed hover intent behavior.</td>
          </tr>
          <tr>
            <td><code>SettingsControllerTest</code> and settings partial contracts</td>
            <td>Guard preference normalization, Trigger bounds, notification position keys, rendered controls, and settings UI contracts.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Policies Going Forward</h2>
      <ul class="doc-list">
        <li>Protected business work rows must originate from the canonical protected access gate before any report, export, cache, warmer, or audit path can use them.</li>
        <li>Old compatibility paths must be classified as required, migrated, or removed. We will not keep old behavior alive because it is convenient.</li>
        <li>Data migrations require report-only audit, checkpoint, fix, report-only verification, and regression tests.</li>
        <li>Compatibility that protects real user data stays until telemetry and audits prove removal is safe.</li>
        <li>Pay-period and earnings code must use calendar-aware date math across timezone and DST boundaries.</li>
        <li>Skipped tests must have a clear environment reason; deterministic product regressions should run in the normal gate.</li>
        <li>Security comparisons, origin checks, IP handling, and debug controls should fail closed and use constant-time or allowlisted behavior where applicable.</li>
        <li>Settings controls must be visibly connected to the preference they configure, bounded by persisted defaults, and protected by rendered-contract tests.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>What This Does Not Claim</h2>
      <p>
        This was not a claim that every compatibility path is gone or every data
        migration is complete. It is the opposite: we removed what the audits
        proved safe to remove, fixed what was clearly wrong, and kept guardrails
        around compatibility that still protects real user data.
      </p>
      <p>
        It was also not a security incident disclosure. It is a public record of
        remediation work, bugs found, verification performed, and the policies we
        will use when future cleanup touches protected work data or historical
        compatibility behavior.
      </p>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
