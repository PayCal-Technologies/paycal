<?php
/**
 * Public Transparency: Settings Remediation - June 2026
 *
 * PURPOSE:
 * Explain the settings navigation and appearance remediation work, the bugs
 * found during the investigation, the safeguards installed, and the specific
 * regression tests that now protect the behavior.
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
$pageTitle = 'Settings Navigation and Appearance Remediation - [PayCal]';
$pageLabel = 'Settings Navigation and Appearance Remediation';
$pageMetaDescription = 'PayCal remediated settings navigation and appearance controls by adding bounded sidebar hover timing, clearer sidebar grouping, live accent previews, canonical notification position values, and contrast-tested settings navigation states.';
$pageMetaDescriptionLong = 'PayCal completed a June 2026 remediation of settings navigation and appearance controls, including sidebar hover intent, grouped sidebar utilities, spectrum accent swatches, live accent previews, notification position semantics, and settings navigation contrast safeguards.';
$pageSocialTitle = 'Settings Navigation and Appearance Remediation';
$pageOgDescription = 'A public summary of PayCal\'s June 2026 settings remediation, the bugs found, what changed, and the tests that now guard the behavior.';
$pageTwitterTitle = 'Settings Navigation and Appearance Remediation';
$pageTwitterDescription = 'How PayCal remediated settings navigation, accent controls, notification positioning, and settings nav contrast in June 2026.';
$pageDcTitle = 'Settings Navigation and Appearance Remediation';
$pageDcDescription = $pageMetaDescription;
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Settings Remediation</span>
  </nav>

  <header class="doc-article-header">
    <h1>Settings Navigation and Appearance Remediation</h1>
    <p class="deck">
      In June 2026 we completed a focused remediation pass on PayCal's settings
      navigation and appearance controls. The work started with small usability
      bugs, but the investigation showed a broader theme: settings controls must
      be predictable, visible in the product, bounded by clear defaults, and
      protected by regression tests.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-19">2026-06-19</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; Release target: <code>v1.059.000</code></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Executive Summary</h2>
      <table class="doc-table" aria-label="Settings remediation summary">
        <tbody>
          <tr>
            <td><strong>Area affected</strong></td>
            <td>Settings sidebar behavior, sidebar grouping, appearance preferences, accent colors, notification placement, and settings nav states</td>
          </tr>
          <tr>
            <td><strong>Risk we addressed</strong></td>
            <td>Controls could feel instant, unclear, visually inconsistent, or disconnected from the UI they claimed to configure</td>
          </tr>
          <tr>
            <td><strong>Core fix</strong></td>
            <td>Settings controls now have bounded values, clearer visual models, shared accent tokens, live preview feedback, and focused regression coverage</td>
          </tr>
          <tr>
            <td><strong>Implementation commit</strong></td>
            <td><code>6316e1cd</code> - Remediate settings navigation and appearance controls</td>
          </tr>
          <tr>
            <td><strong>Release verification</strong></td>
            <td>Full PHPUnit passed: 2,318 tests, 19,222 assertions, 29 skipped</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>What Triggered The Remediation</h2>
      <p>
        The first bug was interaction timing. The collapsed sidebar could expand
        immediately when the pointer crossed it, and that behavior was especially
        noticeable when the pointer left the browser window. A setting that feels
        accidental is a reliability issue, even when it is not a data issue.
      </p>
      <p>
        While testing that flow, we found related issues in the same settings
        surface:
      </p>
      <ul class="doc-list">
        <li>The sidebar utility icons were not anchored at the bottom and the collapsed and expanded layouts did not communicate the same grouping.</li>
        <li>The settings icon belonged with Help and Sign Out, but it was visually mixed into business navigation.</li>
        <li>The accent color control was a dropdown even though another part of PayCal already used color swatches for this type of choice.</li>
        <li>The accent preference did not provide enough immediate evidence that the selected color affected real interface elements.</li>
        <li>The Theme and Mode controls had drifted from the intended side-by-side layout.</li>
        <li>The notification position picker was a flat segmented control, so its layout did not match the spatial meaning of the options.</li>
        <li>The settings nav hover state inherited a white global link hover background, creating poor visual contrast in the dark settings header.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Investigation Rationale</h2>
      <p>
        We treated the request as more than a styling cleanup because settings
        screens define durable user preferences. The investigation followed the
        path from stored values, through controller normalization, through
        rendered controls, and into the JavaScript and CSS that users interact
        with.
      </p>
      <ul class="doc-list">
        <li>For sidebar timing, we checked both the stored preference and the pointer event lifecycle so delayed hover intent cancels cleanly on pointer exit, document leave, and window blur.</li>
        <li>For sidebar grouping, we compared the expanded labels against the collapsed icon-only layout so both states communicate the same personal, business, and utility groups.</li>
        <li>For accent colors, we searched where the accent preference was emitted and where the UI actually consumed color tokens. That exposed that the preference needed stronger shared tokens and a live preview.</li>
        <li>For notification position, we changed both the visible labels and the saved values so the model uses top/bottom language consistently.</li>
        <li>For nav contrast, we checked inactive, hover, active, active-hover, and focus states instead of fixing only the white hover block.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>What We Fixed</h2>
      <table class="doc-table" aria-label="Settings remediation fixes">
        <thead>
          <tr>
            <th>Area</th>
            <th>Fix</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Sidebar hover trigger</td>
            <td>Added a persisted Trigger preference with a conservative 400 ms default, 200 ms minimum, 3,000 ms maximum, and 200 ms step size. The sidebar now waits for sustained hover intent before expanding.</td>
          </tr>
          <tr>
            <td>Pointer cancellation</td>
            <td>Delayed sidebar timers cancel when the pointer leaves the sidebar zone, the document, or the browser window, preventing stale hover timers from expanding the sidebar later.</td>
          </tr>
          <tr>
            <td>Sidebar grouping</td>
            <td>Rebuilt the sidebar order into Personal, Business, and Utility groups. Settings, Help, and Sign Out now form the bottom utility group in both collapsed and expanded states.</td>
          </tr>
          <tr>
            <td>Appearance labels</td>
            <td>Renamed Accent color to Accent, Density preset to Density, and Variant to Mode. Theme and Mode now sit side by side with labels above their inputs.</td>
          </tr>
          <tr>
            <td>Accent colors</td>
            <td>Replaced the dropdown with 16 representative spectrum swatches, sorted visually by output color and backed by shared accent tokens.</td>
          </tr>
          <tr>
            <td>Accent feedback</td>
            <td>Added hover popovers and a live preview window that updates with the selected accent. The redundant swatch label below the color row was removed.</td>
          </tr>
          <tr>
            <td>Notification position</td>
            <td>Replaced the flat control with a spatial grid: Full Top spans the first row, top-left/top-center/top-right and bottom-left/bottom-center/bottom-right sit in the middle rows, and Full Bottom spans the last row.</td>
          </tr>
          <tr>
            <td>Saved notification keys</td>
            <td>Changed saved values to canonical top/bottom keys such as <code>top-left</code>, <code>bottom-center</code>, <code>full-top</code>, and <code>full-bottom</code>. We intentionally did not preserve old upper/lower aliases.</td>
          </tr>
          <tr>
            <td>Settings nav</td>
            <td>Renamed Data &amp; Consent to Data/Consent and replaced the inherited white hover with component-owned hover, active, and focus states.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Accent Color Policy</h2>
      <p>
        Accent controls should not be decorative preferences that barely affect
        the product. The remediation made accent selection visible in the settings
        UI and tied it to shared tokens that feature code can consume consistently.
      </p>
      <ul class="doc-list">
        <li>The palette now offers 16 representative spectrum colors: red, orange, amber, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, and rose.</li>
        <li>Blue remains the theme default because it is the established PayCal accent.</li>
        <li>The selected accent feeds shared tokens including <code>--color-accent</code>, focus treatments, selected controls, button states, and calendar preview states.</li>
        <li>The preview demonstrates the color against UI patterns such as a selected calendar cell, an earnings summary, buttons, and small status elements.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Contrast And State Checks</h2>
      <p>
        The settings nav issue was a good example of why local components should
        own their interaction states. A global link hover rule was technically
        valid elsewhere, but wrong inside the dark settings header.
      </p>
      <table class="doc-table" aria-label="Settings nav contrast checks">
        <thead>
          <tr>
            <th>State</th>
            <th>Checked contrast ratio</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Inactive nav text on panel background</td>
            <td>9.08:1</td>
          </tr>
          <tr>
            <td>Hover text on hover background</td>
            <td>11.52:1</td>
          </tr>
          <tr>
            <td>Active nav text on panel background</td>
            <td>14.13:1</td>
          </tr>
          <tr>
            <td>Active hover text on hover background</td>
            <td>11.19:1</td>
          </tr>
          <tr>
            <td>Focus ring against panel background</td>
            <td>6.15:1</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Regression Tests Added Or Updated</h2>
      <p>
        We added guardrails at the controller, rendered markup, JavaScript, token,
        and contract levels. The goal is to make future regressions visible during
        normal test runs, not during manual UI review after the fact.
      </p>
      <table class="doc-table" aria-label="Settings remediation test coverage">
        <thead>
          <tr>
            <th>Test suite</th>
            <th>What it now looks for</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>SettingsControllerTest</code></td>
            <td>Normalizes appearance preferences, enforces sidebar Trigger bounds, and accepts only the canonical top/bottom notification position keys.</td>
          </tr>
          <tr>
            <td><code>SettingsPageTest</code></td>
            <td>Verifies the Trigger slider is rendered with the expected default, minimum, maximum, and step values.</td>
          </tr>
          <tr>
            <td><code>SettingsBacklogPartialContractTest</code></td>
            <td>Confirms the settings partial exposes the new sidebar Trigger control, accent swatches, live accent preview, and Full Top/Full Bottom notification position controls.</td>
          </tr>
          <tr>
            <td><code>SidebarNavigationContractTest</code></td>
            <td>Locks the sidebar grouping and order, including Settings, Help, and Sign Out as bottom utility actions, and checks that delayed hover behavior cancels correctly.</td>
          </tr>
          <tr>
            <td><code>ThemeButtonTokenContractTest</code></td>
            <td>Verifies the 16 accent presets, default blue selection, generated swatch markup, live preview markup, and shared accent CSS tokens.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Verification Snapshot</h2>
      <ul class="doc-list">
        <li>Full PHPUnit passed with 2,318 tests, 19,222 assertions, and 29 skipped tests.</li>
        <li>The pre-commit quick suite passed with 1,417 tests and 9,471 assertions during the implementation commit.</li>
        <li>PHP linting passed for the changed PHP files.</li>
        <li><code>git diff --check</code> passed before commit, confirming no whitespace errors in the changed files.</li>
        <li>The settings nav hover, active, and focus states were checked against WCAG contrast expectations.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Policies Going Forward</h2>
      <ul class="doc-list">
        <li>Interactive settings that can trigger layout changes must have clear timing, cancellation, defaults, and persisted bounds.</li>
        <li>Settings controls should visually represent the thing being configured when the option is spatial, color-based, or otherwise non-textual.</li>
        <li>Accent preferences must be backed by shared tokens and a visible live preview. We should not ship dead preferences that users cannot see in the product.</li>
        <li>Component nav hover, active, and focus states should be locally owned and contrast-checked instead of relying on broad global link rules.</li>
        <li>Saved preference keys should use the current product language. For early internal testing changes, we will avoid legacy aliases unless compatibility is deliberately required.</li>
        <li>Regression tests should cover the rendered settings contract, not just backend normalization, because these bugs were mostly visible at the UI boundary.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>What This Does Not Claim</h2>
      <p>
        This remediation was not a security incident, and it does not claim that
        every settings surface is finished forever. It is a public record of a
        usability and consistency remediation that now has explicit tests,
        contrast checks, and release documentation behind it.
      </p>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
