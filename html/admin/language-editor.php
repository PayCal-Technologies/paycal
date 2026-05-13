<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;

require_once __DIR__ . '/../config.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'Language Editor - [PayCal]';
$message = '&nbsp;';


Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/language-editor/');

$pageLabel = 'Language Editor';
$pageLanguage = USER_LANGUAGE;

$supportedLanguages = ['en', 'de', 'fr', 'es', 'it', 'nl', 'pt', 'hi', 'tl', 'tr'];

if ('GET' === $_SERVER['REQUEST_METHOD'] && isset($_GET['lang'])) {
  header('Content-Type: application/json; charset=utf-8');

  $lang = (string) InputSanitizer::getString('lang');

  // Allow only known language codes for this editor endpoint.
  if (!in_array($lang, $supportedLanguages, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid language code.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
  }

  // Build path to strings directory
  $file = __DIR__ . "/../../strings/{$lang}.txt";
  if (file_exists($file)) {
    $content = file_get_contents($file);
    if ($content === false) {
      echo json_encode(['success' => false, 'error' => 'Failed to read language file.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
      exit;
    }

    echo json_encode(['success' => true, 'content' => $content], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  } else {
    echo json_encode(['success' => false, 'error' => 'Language file not found.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  }

  exit;
}

if ('POST' === $_SERVER['REQUEST_METHOD']) {
  header('Content-Type: application/json; charset=utf-8');

  // Keep writes behind the API controller capability flow only.
  echo json_encode([
    'success' => false,
    'error' => 'Direct language updates are disabled on this endpoint.',
  ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

  exit;
}

// Default: show editor
require_once HTML.'/header.php';
$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin'), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
?>

<section class='lang-editor panel' aria-label='Language Editor'>
  <div class='lang-editor__header'>
    <h2>Language Editor</h2>
    <p class='lang-editor__desc'>Edit and save language bundles for all supported locales.</p>
  </div>
  <div class='lang-editor__tabs'>
    <?php
    $langs = ['en' => 'English', 'de' => 'German', 'fr' => 'French', 'es' => 'Spanish', 'it' => 'Italian', 'nl' => 'Dutch', 'pt' => 'Portuguese', 'hi' => 'Hindi', 'tl' => 'Tagalog', 'tr' => 'Turkish'];
foreach ($langs as $code => $name) {
  $selected = 'en' === $code ? 'true' : 'false';
  echo "<button class='lang-editor__tab-btn' data-lang='{$code}' aria-selected='{$selected}'>{$name}</button>";
}
?>
  </div>
  <div class='lang-editor__content' id='content_shared'>
    <h2 id='lang_title'>English Language Editor</h2>
    <textarea class='lang-editor__textarea' id='language_textarea' rows='40' placeholder='Language file content will load here...'></textarea>
    <button class='btn btn_primary' id='save_btn'>Save Changes</button>
  </div>
</section>

<script defer src="<?php echo Environment::appURL('js/admin/'); ?>" type="module" nonce="<?php echo CSP_NONCE; ?>"></script>

<?php
require_once HTML.'/footer.php';
?>
