<?php
/**
 * Public Transparency: How We Made the Business Members Page ~100x Faster — June 2026
 *
 * PURPOSE: Engineering write-up of the Business Members page performance fix.
 * Explains the N+1 query pattern and full-recomputation problems, the Redis
 * pipelining and materialized cache solution, and the measured impact.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_MEMBERS_PERFORMANCE_2026_06_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_MEMBERS_PERFORMANCE_2026_06_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_MEMBERS_PERFORMANCE_2026_06_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">How We Made the Business Members Page ~100x Faster</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_MEMBERS_PERFORMANCE_2026_06_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      The Business Members page was taking about 1.8 seconds of server time on every load.
      We traced it to two compounding design flaws, fixed both, and the same page now renders
      from cache in single-digit milliseconds. This article explains what was slow, why,
      and exactly what we changed.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-09">2026-06-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/members-performance-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section success">
      <div class="subject-example-cutout" role="note" aria-label="Performance improvement summary">
        <h2>Performance Improvement</h2>
        <ul class="doc-fact-list">
          <li><strong>Before:</strong> ~1.8 seconds</li>
          <li><strong>After:</strong> &lt;10 milliseconds</li>
          <li><strong>Improvement:</strong> ~100x+</li>
        </ul>
        <p>Measured using PayCal Lens.</p>
      </div>
      <p>
        For business administrators, the Members page now appears effectively instant, even for
        larger businesses.
      </p>
    </section>

    <section class="doc-section highlight">
      <h2>Executive Summary</h2>
      <table class="doc-table" aria-label="Executive summary of the performance fix">
        <tbody>
          <tr>
            <td><strong>Page affected</strong></td>
            <td>Business Members — the grid listing every member of a business with computed financial columns</td>
          </tr>
          <tr>
            <td><strong>Before</strong></td>
            <td>~1.8 seconds of server time per page load</td>
          </tr>
          <tr>
            <td><strong>After</strong></td>
            <td>Single-digit milliseconds on cache hits (~100x+ improvement); cache misses also faster than the old path</td>
          </tr>
          <tr>
            <td><strong>Root causes</strong></td>
            <td>An N+1 query pattern against Redis, and full recomputation of a year of payroll math on every view</td>
          </tr>
          <tr>
            <td><strong>The fix</strong></td>
            <td>Batched (pipelined) Redis lookups, plus a materialized cache of the finished grid data with explicit invalidation</td>
          </tr>
          <tr>
            <td><strong>Data freshness</strong></td>
            <td>Cache is invalidated immediately on any member-related change; a 5-minute expiry bounds staleness as a safety net</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Why We Are Publishing This</h2>
      <p>
        Performance is part of transparency. When a page is slow, users deserve to know whether
        the slowness is inherent to the work being done or the result of an avoidable design
        flaw. In this case it was the latter — twice over — and we think the details are worth
        sharing because both flaws are among the most common performance mistakes in web
        software.
      </p>
      <p>
        Nothing in this article involves a security issue or any user data exposure. It is
        purely an engineering story about making a slow page fast.
      </p>
    </section>

    <section class="doc-section">
      <h2>What the Page Does</h2>
      <p>
        The Business Members page lists every member of a business. Alongside each member's
        name and role, the grid shows five computed financial columns:
      </p>
      <ul class="doc-list">
        <li><strong>Year-to-date gross</strong> — total earnings so far this year</li>
        <li><strong>Total hours</strong> — all hours worked this year</li>
        <li><strong>Regular hours</strong> — hours at the standard rate</li>
        <li><strong>Overtime hours</strong> — hours beyond the regular threshold</li>
        <li><strong>Trailing baseline</strong> — a rolling reference figure used for comparison</li>
      </ul>
      <p>
        None of these values are stored anywhere as finished numbers. They are computed from
        each member's raw work entries — the individual shift records stored in our Redis
        database. Producing one row of the grid means loading a member's profile, loading
        their full year of work entries, splitting hours into regular and overtime, and
        summing gross pay. Multiply that by every member of the business, and that is the
        work the page does.
      </p>
    </section>

    <section class="doc-section">
      <h2>The Problem, Part 1 &mdash; The N+1 Query Pattern</h2>
      <p>
        The original implementation made <strong>one</strong> query to fetch the list of members,
        and then — for each of roughly 100 members — made <strong>separate, sequential</strong>
        round-trips to Redis: one for the member's profile, and more for their full year of
        work entries.
      </p>
      <p>
        Small businesses with a handful of members were largely unaffected; the stacked latency
        only becomes noticeable as businesses grow to dozens or hundreds of members, where
        the per-member round trips accumulate into seconds of server time.
      </p>
      <p>
        This is the classic &ldquo;N+1&rdquo; pattern: one query to get the list, then N more
        queries issued one at a time for the items in it. Each individual Redis lookup is
        fast — well under a millisecond of actual work — but every round-trip also pays a
        fixed cost in network latency. Issued sequentially, those costs do not overlap; they
        stack linearly. Hundreds of sequential round-trips meant hundreds of stacked latency
        payments before the page could render anything.
      </p>
      <p>
        The database was never the bottleneck. The <em>conversation</em> with the database was.
      </p>
    </section>

    <section class="doc-section">
      <h2>The Problem, Part 2 &mdash; Recomputing Everything on Every View</h2>
      <p>
        The second flaw compounded the first. All of that payroll math — splitting a year of
        work entries into regular and overtime hours, computing year-to-date gross, deriving
        the trailing baseline, for every member — was redone <strong>from scratch on every
        single page view</strong>.
      </p>
      <p>
        Work entries do not change very often. Between two consecutive views of the Members
        page, the underlying data is almost always identical. Yet every visit paid the full
        cost of recomputing results that had just been computed moments earlier and then
        thrown away.
      </p>
      <p>
        Combined, the two flaws produced about <strong>1.8 seconds of server time per page
        load</strong>, measured with PayCal Lens, our built-in server timing instrumentation.
      </p>
    </section>

    <section class="doc-section">
      <h2>The Fix, Part 1 &mdash; Redis Pipelining</h2>
      <p>
        Redis supports <em>pipelining</em>: sending a batch of commands in a single round-trip
        and receiving all of the answers together. Instead of asking one question, waiting,
        asking the next, and waiting again, the server now asks all of its questions at once.
      </p>
      <p>
        We added a batched lookup method, <code>Database::pipelineHgetall()</code>, and converted
        the members grid to use it. All member profile lookups are gathered into a single
        round-trip, and all work-entry lookups into another — rather than one round-trip per
        member per data type.
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — one round-trip per member, latency stacks linearly
foreach ($memberIds as $id) {
    $profiles[$id] = Database::hgetall($profileKey($id));
}

// After — one round-trip for the whole batch
$profiles = Database::pipelineHgetall(array_map($profileKey, $memberIds));</code></pre>
      </div>
      <p>
        This change alone collapsed hundreds of sequential latency payments into a handful.
      </p>
    </section>

    <section class="doc-section">
      <h2>The Fix, Part 2 &mdash; A Materialized Cache</h2>
      <p>
        Pipelining makes the computation cheaper to feed; the second fix avoids repeating the
        computation at all. We introduced <code>BusinessMembersCache</code>, a server-side cache
        that stores the <em>finished</em> computed grid data — the per-member financial
        summaries — in Redis.
      </p>
      <p>Two rules govern cache freshness:</p>
      <ul class="doc-list">
        <li>
          <strong>A 5-minute expiry.</strong> Every cached grid automatically expires after
          5 minutes, bounding how old the data can possibly be.
        </li>
        <li>
          <strong>Explicit invalidation on change.</strong> Any member-related change — a role
          update, a member added, a member removed — deletes the cached grid immediately.
          The cache is invalidated immediately after relevant edits, ensuring fresh data is
          generated on the next request.
        </li>
      </ul>
      <p>
        Member identity and permission checks are deliberately <em>not</em> cached. Every
        request still runs the full access-control path; only the expensive financial
        arithmetic is reused.
      </p>
    </section>

    <section class="doc-section success">
      <h2>The Impact</h2>
      <ul class="doc-list">
        <li>
          <strong>Cache hits serve the grid in single-digit milliseconds</strong> — a
          ~100x+ improvement over the ~1.8 seconds the page previously took.
        </li>
        <li>
          <strong>Cache misses are still faster than the old page ever was</strong>, because
          the recomputation now runs on pipelined, batched lookups instead of hundreds of
          sequential round-trips.
        </li>
        <li>
          <strong>Correctness is tested.</strong> Contract and unit tests cover the cache
          invalidation behavior — verifying that role updates and connection changes evict
          the cached grid, and that expired or mismatched cache entries are never served.
        </li>
      </ul>

      <figure class="doc-figure">
        <img src="/transparency/members-performance-2026-06/images/lens-impact-comparison.png"
             alt="Performance improvement summary showing ~1.8 seconds before, under 10 milliseconds after, and roughly 100x improvement measured with PayCal Lens"
             loading="lazy"
             width="906"
             height="250">
        <figcaption class="doc-figure-caption">PayCal Lens measured server time before and after the fix.</figcaption>
      </figure>

      <figure class="doc-figure">
        <img src="/transparency/members-performance-2026-06/images/lens-trace-before.png"
             alt="PayCal Lens performance summary before optimization showing 1812 milliseconds total duration with financial summaries, profile hydration, and sequential work entry lookups as the slowest paths"
             loading="lazy"
             width="906"
             height="481">
        <figcaption class="doc-figure-caption">Before: PayCal Lens ranked the per-member financial recomputation and sequential Redis round trips as the dominant costs (~1812&nbsp;ms total).</figcaption>
      </figure>

      <figure class="doc-figure">
        <img src="/transparency/members-performance-2026-06/images/lens-trace-after.png"
             alt="PayCal Lens performance summary after optimization showing 7 milliseconds total duration with BusinessMembersCache get as the top path and cache hit status"
             loading="lazy"
             width="906"
             height="481">
        <figcaption class="doc-figure-caption">After: the same page on a cache hit completes in single-digit milliseconds (~7&nbsp;ms), with the materialized grid read as the primary work.</figcaption>
      </figure>

      <figure class="doc-figure">
        <img src="/transparency/members-performance-2026-06/images/members-grid-loaded.png"
             alt="Business members grid table showing member names, roles, year-to-date gross, and total hours columns populated"
             loading="lazy"
             width="906"
             height="323">
        <figcaption class="doc-figure-caption">The members grid loads immediately on repeat visits while access control still runs on every request.</figcaption>
      </figure>
    </section>

    <section class="doc-section highlight">
      <h2>What We Took Away From This</h2>
      <ul class="doc-list">
        <li>
          <strong>Measure before optimizing.</strong> PayCal Lens told us exactly where the
          1.8 seconds went. Without per-request timing instrumentation, both flaws would have
          been guesses.
        </li>
        <li>
          <strong>Latency stacks; batch it.</strong> Many small fast queries issued
          sequentially are slower than one large batched query. Round-trips, not data volume,
          dominated this page.
        </li>
        <li>
          <strong>Cache finished work, invalidate eagerly.</strong> A cache is only trustworthy
          if every write path that affects it also clears it. The expiry is a safety net, not
          the mechanism.
        </li>
      </ul>
      <p>
        We will continue publishing engineering write-ups like this one to the
        <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a>.
      </p>
    </section>

    <section class="doc-section highlight">
      <h2>Engineering Facts</h2>
      <ul class="doc-fact-list">
        <li><strong>Commit(s):</strong> <code>3db2229b</code>, <code>2b3eafb8</code>, <code>f63773ea</code></li>
        <li><strong>Files Changed:</strong> 14 (under <code>html/</code>)</li>
        <li><strong>Tests Added:</strong> 12 (<code>BusinessMembersCacheTest</code> — 9 cases; <code>LensRenderTest</code> — 3 cases)</li>
        <li><strong>Tests Passing:</strong> 1901 (full PHPUnit suite, June 2026)</li>
        <li><strong>Performance Impact:</strong> ~1.8&nbsp;s → ~7&nbsp;ms (&lt;10&nbsp;ms on cache hits)</li>
        <li><strong>Production Status:</strong> Deployed</li>
      </ul>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
