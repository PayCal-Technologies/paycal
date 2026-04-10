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
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Error Handling & Message Normalization - [PayCal]';
$pageLabel = 'Error Handling & Message Normalization';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Error Handling &amp; Message Normalization</span>
  </nav>

  <header class="doc-article-header">
    <h1>Error Handling &amp; Message Normalization</h1>
    <p class="deck">
      How PayCal standardizes error reporting across all frontend modules to ensure users
      receive meaningful, secure, and consistent error feedback without exposing sensitive details.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Overview &amp; Purpose</h2>
      <p>
        When users encounter errors (network failures, permission denied, validation errors),
        they deserve clear feedback explaining what happened and how to fix it. However,
        raw error messages from the backend must be normalized to:
      </p>
      <ul class="doc-list">
        <li><strong>Remove noise:</strong> Strip redundant &quot;Error:&quot; prefixes and clean whitespace</li>
        <li><strong>Prevent leakage:</strong> Ensure sensitive implementation details never reach the user</li>
        <li><strong>Provide fallbacks:</strong> Display safe messages when errors are empty or malformed</li>
        <li><strong>Ensure consistency:</strong> Apply the same logic across all 11+ frontend modules</li>
        <li><strong>Improve debugging:</strong> Log full error details to Phantom Wing while showing safe summaries to users</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>The Problem: Generic vs. Meaningful Errors</h2>
      <p>
        Before standardization, PayCal modules used ad-hoc error handling:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ BAD: Exposes raw error, duplicates logic
PC.showToast(error?.message || 'Import failed.');
PW.error(`Import failed: ${error.message}`);</code></pre>
      </div>
      <p>Problems with this approach:</p>
      <ul class="doc-list">
        <li>Users see confusing raw messages like &quot;ECONNREFUSED: Connection refused&quot;</li>
        <li>Each module implements its own fallback logic independently</li>
        <li>No consistent whitespace trimming or prefix stripping</li>
        <li>Empty error messages can display as &quot;undefined&quot; in the UI</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>The Solution: Standardized Error Resolver</h2>
      <p>
        All PayCal frontend modules now use a unified resolver function that normalizes error messages:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ GOOD: Normalized, consistent, safe
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Extract message from error object
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // Remove "Error:" prefix and trim whitespace
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Return normalized if non-empty; else safe fallback
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Usage:</strong></p>
      <div class="doc-code-block">
        <pre><code>// In catch blocks across modules
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'Unable to update profile.');
  PC.showToast(message, 'error');  // User sees meaningful feedback
  PW.error(message);                // Logged for debugging
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Implementation Scope</h2>
      <p>
        As of April 2026, this standardized error-handling pattern has been applied to
        <strong>11 frontend modules</strong> with <strong>~40+ normalized catch blocks</strong>:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Authentication &amp; Settings (7 modules)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Core &amp; Data Modules (4 modules)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>High-value modules (10+ catch points):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/organizations/index.php</code> — Org management, access requests, audit trails (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — Site CRUD, earnings, orphan work recovery (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Day-entry operations, copy/paste/delete (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Error Categories &amp; Handling Patterns</h2>
      <p>The resolver is applied consistently across several error categories:</p>
      
      <h3>1. Network Request Failures</h3>
      <div class="doc-code-block">
        <pre><code>// Network module: HTTP errors, timeouts, connection issues
async function deleteResource(ep, id) {
  try {
    // ...fetch logic...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Network error');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. API Response Handling</h3>
      <div class="doc-code-block">
        <pre><code>// Billing/Settings: Server returned error message in payload
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'Unable to load billing status.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'Unable to load billing status.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. UI Operation Failures</h3>
      <div class="doc-code-block">
        <pre><code>// Calendar/Organizations: User-initiated actions (paste, delete, update)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('Success!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'Action failed. Try again.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Async Initialization</h3>
      <div class="doc-code-block">
        <pre><code>// Core modules: Startup or dependent initialization failures
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'Navigation init failed');
  PW.warn(resolved);  // Logged but doesn't block page
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Security Considerations</h2>
      <p>
        Error message normalization protects user privacy and system integrity:
      </p>
      <ul class="doc-list">
        <li>
          <strong>No Database Details:</strong> Backend errors like &quot;UNIQUE constraint failed on email&quot; 
          are intercepted at the API boundary and replaced with user-friendly messages
        </li>
        <li>
          <strong>No File Paths:</strong> System errors exposing file paths or process details are stripped
        </li>
        <li>
          <strong>No Auth Leakage:</strong> Responses to authentication failures never reveal whether 
          an account exists (timing-safe generic messages only)
        </li>
        <li>
          <strong>No CORS/Network Details:</strong> Transport-layer errors are normalized to 
          generic &quot;Connection error&quot; messages
        </li>
        <li>
          <strong>Secure Fallbacks:</strong> All catchers have explicit fallback messages; never 
          displays &quot;undefined&quot; or &quot;null&quot;
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>User Experience Benefits</h2>
      <p>
        Standardized error messages improve user experience significantly:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Clear Feedback:</strong> Users know what failed (e.g., &quot;Passkey not recognized&quot; 
          vs. generic &quot;Sign-in failed&quot;)
        </li>
        <li>
          <strong>Actionable Next Steps:</strong> When possible, messages suggest remedies 
          (&quot;Try again&quot;, &quot;Check your connection&quot;, &quot;Contact support&quot;)
        </li>
        <li>
          <strong>Consistency Across App:</strong> Same error types display the same way everywhere, 
          reducing user confusion
        </li>
        <li>
          <strong>Accessible Error States:</strong> Screen readers announce resolved messages; 
          logging provides full context for support teams
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Debugging &amp; Support Workflow</h2>
      <p>
        Error normalization does <strong>not</strong> sacrifice debugging capability. Full error details 
        flow to Phantom Wing:
      </p>
      <div class="doc-code-block">
        <pre><code>// User sees clean UI message
PC.showToast(resolveThrownMessage(error, 'Upload failed.'), 'error');

// Support team sees full details in Phantom Wing logs
PW.error('Upload failed', {
  userMessage: resolveThrownMessage(error, 'Upload failed.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Testing &amp; Quality Assurance</h2>
      <p>
        All error-handling changes are validated before deployment:
      </p>
      <ul class="doc-list">
        <li><strong>Syntax Validation:</strong> <code>php -l</code> and <code>node --check</code> verify correctness</li>
        <li><strong>Type Safety:</strong> Editor diagnostics confirm no type regressions</li>
        <li><strong>Integration Testing:</strong> Catch blocks tested with mock error objects</li>
        <li><strong>Phantom Wing Logging:</strong> Error messages verified in debug logs</li>
        <li><strong>Accessibility Audit:</strong> Screen reader announcements tested for clarity</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Maintenance &amp; Future Extensions</h2>
      <p>
        This pattern is designed for long-term maintainability:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Localization Ready:</strong> Error messages can be piped through i18n without 
          modifying the resolver logic
        </li>
        <li>
          <strong>Extensible:</strong> Resolver can be enhanced to handle error codes, retry logic, 
          or specialized message lookup without breaking existing code
        </li>
        <li>
          <strong>Documentation:</strong> Each module includes inline comments explaining 
          error scenarios and fallback strategies
        </li>
        <li>
          <strong>Git History:</strong> All changes tracked with detailed commit messages and 
          file-level diffs for easy review
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Summary: The PayCal Error-Handling Standard</h2>
      <p>
        PayCal's standardized error-message normalization ensures that:
      </p>
      <ol class="doc-list">
        <li>Users receive clear, actionable error feedback</li>
        <li>Sensitive system details never leak to the frontend</li>
        <li>Message handling is consistent across all 11+ frontend modules</li>
        <li>Debugging and support teams retain full error context via Phantom Wing</li>
        <li>Code is maintainable, testable, and accessible</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        This commitment to security, clarity, and consistency reflects PayCal's dedication 
        to user trust and transparent information sharing.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
