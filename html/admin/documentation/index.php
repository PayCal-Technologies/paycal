<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Render;

require_once '../../config.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'Operations Documentation - [PayCal]';
$pageLabel = 'Operations Documentation';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/documentation/');

require_once HTML.'/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';

/**
 * Convert Markdown to HTML (basic converter for common patterns).
 * Supports: headers, bold, italic, code, links, lists, code blocks.
 */
function markdownToHtml(string $markdown): string
{
  $html = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');
  
  // Code blocks (```...```)
  $html = preg_replace_callback(
    '/```([a-z]*)\n(.*?)\n```/s',
    fn($m) => '<pre><code class="language-'.$m[1].'">' . $m[2] . '</code></pre>',
    $html
  ) ?? $html;
  
  // Headers
  $html = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $html) ?? $html;
  $html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html) ?? $html;
  $html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $html) ?? $html;
  
  // Horizontal rules
  $html = preg_replace('/^---$/m', '<hr>', $html) ?? $html;
  
  // Bold
  $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html) ?? $html;
  $html = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $html) ?? $html;
  
  // Italic
  $html = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $html) ?? $html;
  $html = preg_replace('/_(.+?)_/s', '<em>$1</em>', $html) ?? $html;
  
  // Inline code
  $html = preg_replace('/`([^`]+?)`/', '<code>$1</code>', $html) ?? $html;
  
  // Links
  $html = preg_replace('/\[([^\]]+?)\]\(([^\)]+?)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $html) ?? $html;
  
  // Unordered lists
  $html = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $html) ?? $html;
  $html = preg_replace('/(<li>.*?<\/li>)/s', '<ul>$1</ul>', $html) ?? $html;
  
  // Paragraphs
  $lines = explode("\n", $html);
  $result = [];
  $inBlock = false;
  foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) {
      if (!$inBlock && !empty($result) && !str_ends_with(end($result), '</h1>') && !str_ends_with(end($result), '</h2>') && !str_ends_with(end($result), '</h3>') && !str_ends_with(end($result), '</pre>') && !str_ends_with(end($result), '<hr>') && !str_ends_with(end($result), '</ul>')) {
        $result[] = '</p>';
        $inBlock = false;
      }
      continue;
    }
    
    if (!$inBlock && !preg_match('/^<[hlu]/', $line) && !preg_match('/<\/(h|ul|pre)>/', $line) && !str_contains($line, '<hr>')) {
      $result[] = '<p>';
      $inBlock = true;
    }
    
    $result[] = $line;
  }
  if ($inBlock) {
    $result[] = '</p>';
  }
  
  return implode("\n", $result);
}

/**
 * Get list of available documentation files.
 * @return array<array{name: string, path: string, title: string}>
 */
function getDocumentationFiles(): array
{
  $docsDir = dirname(dirname(dirname(__DIR__))) . '/docs';
  $files = [];
  
  if (!is_dir($docsDir)) {
    return $files;
  }
  
  foreach (scandir($docsDir) as $file) {
    if ($file === '.' || $file === '..' || !str_ends_with($file, '.md')) {
      continue;
    }
    
    $path = $docsDir . '/' . $file;
    if (!is_file($path)) {
      continue;
    }
    
    $title = str_replace('_', ' ', str_replace('.md', '', $file));
    $title = str_replace('STRIPE WEBHOOK QUEUE OPERATIONS', 'Stripe Webhook Queue Operations', $title);
    
    $files[] = [
      'name' => $file,
      'path' => $path,
      'title' => $title,
    ];
  }
  
  usort($files, fn($a, $b) => strcasecmp($a['title'], $b['title']));
  
  return $files;
}

$requestedFile = isset($_GET['doc']) && is_scalar($_GET['doc']) ? (string) $_GET['doc'] : '';
$documentationFiles = getDocumentationFiles();
$currentDoc = null;
$currentContent = '';

if (!empty($requestedFile)) {
  foreach ($documentationFiles as $doc) {
    if ($doc['name'] === $requestedFile) {
      $currentDoc = $doc;
      $rawContent = file_get_contents($doc['path']);
      $currentContent = $rawContent ? markdownToHtml($rawContent) : 'Error reading file.';
      break;
    }
  }
}

if ($currentDoc === null && !empty($documentationFiles)) {
  $currentDoc = $documentationFiles[0];
  $rawContent = file_get_contents($currentDoc['path']);
  $currentContent = $rawContent ? markdownToHtml($rawContent) : 'Error reading file.';
}

echo '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin/documentation'), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
?>

<div class="documentation-container">
  <aside class="documentation-sidebar" aria-label="Documentation menu">
    <nav>
      <h2>Documentation</h2>
      <ul class="documentation-menu">
        <?php foreach ($documentationFiles as $doc): ?>
          <li>
            <a href="?doc=<?php echo urlencode($doc['name']); ?>" 
               class="documentation-link <?php echo ($currentDoc && $currentDoc['name'] === $doc['name']) ? 'active' : ''; ?>"
               title="<?php echo htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="documentation-footer">
      <a href="/admin/" class="btn btn_secondary btn_full">Back to Admin</a>
    </div>
  </aside>

  <main class="documentation-content">
    <?php if ($currentDoc): ?>
      <article class="documentation-article">
        <header>
          <h1><?php echo htmlspecialchars($currentDoc['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="documentation-meta">Last updated: <?php echo date('Y-m-d', filemtime($currentDoc['path']) ?: time()); ?></p>
        </header>
        <div class="documentation-body">
          <?php echo $currentContent; ?>
        </div>
      </article>
    <?php else: ?>
      <div class="documentation-empty">
        <p>No documentation files available.</p>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php
require_once HTML.'/footer.php';
