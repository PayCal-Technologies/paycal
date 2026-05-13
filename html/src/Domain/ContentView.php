<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * ContentView — Server-side alternate view renderer for doc-article pages.
 *
 * PURPOSE:
 *   Provides Text (?view=text) and PDF (?view=pdf) alternate rendering for
 *   public content pages (help, transparency, about, blog, media, policies).
 *
 * ARCHITECTURE:
 *   header.php calls ob_start() before <!DOCTYPE> for doc page types.
 *   footer.php calls ContentView::process() after all output is buffered.
 *   ContentView injects the two view-switcher <a> buttons server-side into
 *   the first .doc-article-header, then dispatches to the requested mode.
 *
 * TEXT VIEW (?view=text):
 *   Extracts the <main> region from the buffered HTML, strips markup with
 *   structure-preserving substitutions (headings, bullets, tables), and
 *   serves the result as text/plain; charset=UTF-8.
 *
 * PDF VIEW (?view=pdf):
 *   Injects a nonce'd window.print() script before </body> and serves the
 *   full HTML page. The @media print rules in CSS strip navigation chrome
 *   so the browser's "Save as PDF" produces clean document output.
 *
 * HTML VIEW (default, no ?view= param):
 *   Serves the full HTML page with view-switcher buttons injected.
 *
 * SECURITY:
 *   - ?view= validated against strict whitelist; any other value yields 'html'.
 *   - All URL construction runs through htmlspecialchars for HTML contexts.
 *   - Content-Disposition uses rawurlencode for the filename parameter.
 *   - The PDF print script uses the existing CSP nonce; no unsafe-inline needed.
 *   - strip_tags() on trusted server-generated HTML; no user input is rendered.
 */
class ContentView
{
    /** Page types that support alternate views. Must match footer.php's check. */
    public const DOC_PAGES = [
        'PAGE_HELP',
        'PAGE_TRANSPARENCY',
        'PAGE_ABOUT',
        'PAGE_POLICIES',
        'PAGE_BLOG',
        'PAGE_MEDIA',
    ];

    private static ?string $cachedMode = null;

    /**
     * Returns the requested view mode: 'text', 'pdf', or 'html' (the default).
     * Validates strictly; any value outside the whitelist yields 'html'.
     */
    public static function mode(): string
    {
        if (self::$cachedMode !== null) {
            return self::$cachedMode;
        }

        $raw = $_GET['view'] ?? '';
        if (!is_string($raw)) {
            self::$cachedMode = 'html';
            return self::$cachedMode;
        }

        $normalized = strtolower(trim($raw));
        self::$cachedMode = in_array($normalized, ['text', 'pdf'], true)
            ? $normalized
            : 'html';

        return self::$cachedMode;
    }

    /** Returns true if the page type supports alternate views. */
    public static function isDocPage(string $currentPage): bool
    {
        return in_array($currentPage, self::DOC_PAGES, true);
    }

    /**
     * Build the full current URL from $_SERVER, including the query string.
     * Used internally for switcher link construction.
     */
    public static function currentUrl(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = is_string($_SERVER['HTTP_HOST'] ?? null) ? (string) $_SERVER['HTTP_HOST'] : '';
        $uri    = is_string($_SERVER['REQUEST_URI'] ?? null) ? (string) $_SERVER['REQUEST_URI'] : '/';

        return $scheme . '://' . $host . $uri;
    }

    /**
     * Build the HTML for the two view-switcher link buttons.
     * Embedded server-side into .doc-article-header via injectSwitcherIntoHtml().
     *
     * @param string $currentUrl Full URL of the current page (including query string).
     */
    public static function renderSwitcher(string $currentUrl): string
    {
        // Strip any existing ?view= from the URL to build a clean base
        $base = preg_replace('/([?&])view=[^&]*/u', '$1', $currentUrl);
        $base = rtrim($base ?? $currentUrl, '?&');

        $sep     = str_contains($base, '?') ? '&amp;' : '?';
        $textUrl = htmlspecialchars($base . $sep . 'view=text', ENT_QUOTES, 'UTF-8');
        $pdfUrl  = htmlspecialchars($base . $sep . 'view=pdf',  ENT_QUOTES, 'UTF-8');

        $iconText = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"'
            . ' aria-hidden="true" focusable="false">'
            . '<rect x="1" y="2"    width="12" height="1.3" rx="0.5" fill="currentColor"/>'
            . '<rect x="1" y="5.5"  width="10" height="1.3" rx="0.5" fill="currentColor"/>'
            . '<rect x="1" y="9"    width="11" height="1.3" rx="0.5" fill="currentColor"/>'
            . '<rect x="1" y="12.5" width="7"  height="1.3" rx="0.5" fill="currentColor"/>'
            . '</svg>';

        $iconPdf = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"'
            . ' aria-hidden="true" focusable="false">'
            . '<rect x="2.5" y="1"   width="9"  height="4.5" rx="0.5"'
            .   ' stroke="currentColor" stroke-width="1.1" fill="none"/>'
            . '<rect x="1"   y="5"   width="12" height="5"   rx="1"  '
            .   ' stroke="currentColor" stroke-width="1.1" fill="none"/>'
            . '<rect x="3"   y="8.5" width="8"  height="4.5" rx="0.5"'
            .   ' stroke="currentColor" stroke-width="1.1" fill="none"/>'
            . '<circle cx="10.5" cy="7.5" r="0.8" fill="currentColor"/>'
            . '</svg>';

        return '<div class="doc-view-switcher" role="group" aria-label="Content view options">' . "\n"
            . '  <a class="doc-view-btn" href="' . $textUrl . '"'
            .   ' title="Plain text view" aria-label="Plain text view">'
            . $iconText . '<span class="doc-view-label">Text</span></a>' . "\n"
            . '  <a class="doc-view-btn" href="' . $pdfUrl . '"'
            .   ' title="Save as PDF" aria-label="Save as PDF">'
            . $iconPdf . '<span class="doc-view-label">PDF</span></a>' . "\n"
            . '</div>';
    }

    /**
     * Inject the view-switcher into the first .doc-article-header block in the HTML.
     * Appends the switcher immediately before the closing </header> tag.
     *
     * @param string $html       Full buffered HTML page.
     * @param string $currentUrl Full URL for the switcher link targets.
     */
    private static function injectSwitcherIntoHtml(string $html, string $currentUrl): string
    {
        $switcher = self::renderSwitcher($currentUrl);

        $result = preg_replace(
            '/(<header\b[^>]*\bdoc-article-header\b[^>]*>[\s\S]*?)(<\/header>)/u',
            '$1' . "\n" . $switcher . "\n" . '$2',
            $html,
            1
        );

        return $result ?? $html;
    }

    /**
     * Extract and format article text from the buffered HTML page.
     *
     * Extracts the <main> region, applies structure-preserving substitutions
     * for headings, bullets, and table cells, then strips remaining tags.
     *
     * @param string $html       Full buffered HTML page.
     * @param string $currentUrl Full URL appended as a source reference footer.
     */
    private static function extractText(string $html, string $currentUrl): string
    {
        // Extract the <main> region (article content, without nav/footer chrome)
        if (preg_match('/<main\b[^>]*>([\s\S]*?)<\/main>/ui', $html, $m)) {
            $content = $m[1];
        } else {
            $content = $html;
        }

        // Heading markers — replaced with formatted lines after strip_tags
        $content = preg_replace('/<h1\b[^>]*>/ui', "\n[[H1]]", $content) ?? $content;
        $content = preg_replace('/<h2\b[^>]*>/ui', "\n[[H2]]", $content) ?? $content;
        $content = preg_replace('/<h3\b[^>]*>/ui', "\n[[H3]]", $content) ?? $content;
        $content = preg_replace('/<h[4-6]\b[^>]*>/ui', "\n[[H4]]", $content) ?? $content;
        $content = preg_replace('/<\/h[1-6]>/ui', "[[/H]]\n", $content) ?? $content;

        // Block elements → newlines
        $content = preg_replace('/<\/(p|div|section|article|header|blockquote)>/ui', "\n", $content) ?? $content;
        $content = preg_replace('/<p\b[^>]*>/ui', "\n", $content) ?? $content;
        $content = preg_replace('/<br\s*\/?>/ui', "\n", $content) ?? $content;
        $content = preg_replace('/<hr\s*\/?>/ui', "\n" . str_repeat('-', 72) . "\n", $content) ?? $content;

        // List items
        $content = preg_replace('/<li\b[^>]*>/ui', "\n  \u{2022} ", $content) ?? $content;

        // Table cells (minimal, best-effort)
        $content = preg_replace('/<th\b[^>]*>/ui', ' | ', $content) ?? $content;
        $content = preg_replace('/<td\b[^>]*>/ui', ' | ', $content) ?? $content;
        $content = preg_replace('/<\/tr>/ui', " |\n", $content) ?? $content;

        // Strip remaining tags
        $content = strip_tags($content);

        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Collapse inline whitespace
        $content = preg_replace('/[ \t]+/', ' ', $content) ?? $content;
        $content = preg_replace('/\n[ \t]+/', "\n", $content) ?? $content;
        $content = preg_replace('/\n{3,}/', "\n\n", $content) ?? $content;

        // Apply heading formatting using the [[H1]] ... [[/H]] markers
        $content = preg_replace_callback(
            '/\[\[H1\]\]\s*(.*?)\[\[\/H\]\]/su',
            static function (array $m): string {
                $text = trim($m[1]);
                $bar  = str_repeat('=', min(mb_strlen($text) + 4, 72));
                return "\n\n" . $bar . "\n  " . mb_strtoupper($text) . "\n" . $bar;
            },
            $content
        ) ?? $content;

        $content = preg_replace_callback(
            '/\[\[H2\]\]\s*(.*?)\[\[\/H\]\]/su',
            static function (array $m): string {
                $text = trim($m[1]);
                return "\n\n" . $text . "\n" . str_repeat('-', min(mb_strlen($text), 60));
            },
            $content
        ) ?? $content;

        $content = preg_replace_callback(
            '/\[\[H3\]\]\s*(.*?)\[\[\/H\]\]/su',
            static function (array $m): string {
                return "\n\n### " . trim($m[1]);
            },
            $content
        ) ?? $content;

        $content = preg_replace_callback(
            '/\[\[H4\]\]\s*(.*?)\[\[\/H\]\]/su',
            static function (array $m): string {
                return "\n\n#### " . trim($m[1]);
            },
            $content
        ) ?? $content;

        // Final whitespace cleanup
        $content = trim($content);
        $content .= "\n\n" . str_repeat('-', 72) . "\n" . $currentUrl . "\n";

        return $content;
    }

    /**
     * Process the output buffer according to the view mode.
     *
     * Must be called at the very end of footer.php after all HTML has been
     * rendered into the buffer (including </body></html>).
     *
     * @param string $currentPage The PAGE_* constant for the current page.
     * @param string $cspNonce    The CSP nonce (used for the PDF print script).
     * @param string $pageTitle   The page title (used as the text download filename).
     */
    public static function process(string $currentPage, string $cspNonce, string $pageTitle): void
    {
        $html = ob_get_clean();
        if ($html === false) {
            $html = '';
        }

        $currentUrl = self::currentUrl();
        $mode       = self::mode();

        // Inject view-switcher buttons (always — all modes share the same HTML base)
        $html = self::injectSwitcherIntoHtml($html, $currentUrl);

        switch ($mode) {
            case 'text':
                $text = self::extractText($html, $currentUrl);
                header('Content-Type: text/plain; charset=UTF-8');
                $safeName = preg_replace('/[^a-zA-Z0-9\-_ ]/u', '', $pageTitle);
                $safeName = trim($safeName !== null ? $safeName : 'page');
                header('Content-Disposition: inline; filename="' . rawurlencode($safeName) . '.txt"');
                echo $text;
                exit;

            case 'pdf':
                // Print-trigger is an external nonce'd script loaded in header.php
                // (html/js/print-trigger.js) — no inline script needed here.
                // The @media print CSS in content-views/index.php hides nav chrome.
                echo $html;
                exit;

            default:
                echo $html;
        }
    }
}
