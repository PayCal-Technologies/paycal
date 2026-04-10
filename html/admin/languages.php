<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\InputSanitizer;

require_once __DIR__ . '/../config.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'Language Editor - [PayCal]';
$message = '&nbsp;';


Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/languages/');

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
?>

<section class='flex f_row panel w100 mar_sm pad_md admin-language-editor-panel admin-language-editor-panel--standalone' aria-label='Language Editor'>
  <div class='f_column sidebar pad_md'>
    <h2>Language Editor</h2>
    <p class='text-muted'>Edit and save language bundles for all supported locales.</p>
    <h3>Languages</h3>
    <div class='vertical_tabs'>
      <?php
      $langs = ['en' => 'English', 'de' => 'German', 'fr' => 'French', 'es' => 'Spanish', 'it' => 'Italian', 'nl' => 'Dutch', 'pt' => 'Portuguese', 'hi' => 'Hindi', 'tl' => 'Tagalog', 'tr' => 'Turkish'];
foreach ($langs as $code => $name) {
  $checked = 'en' === $code ? 'checked' : '';
  echo "<input type='radio' id='lang_{$code}' name='lang_tabs' {$checked}>";
  echo "<label for='lang_{$code}' class='tab_label'>{$name} ({$code})</label>";
}
?>
    </div>
  </div>
  <div class='f_column content pad_md'>
    <div class='' id='content_shared'>
      <h2 id='lang_title'>English Language Editor</h2>
      <textarea class='language_textarea' id='language_textarea' rows='24' placeholder='Language file content will load here...'></textarea>
      <button class='btn btn_primary' id='save_btn'>Save Changes</button>
    </div>
  </div>
</section>

<script defer src="<?php echo Environment::appURL('js/admin/'); ?>" type="module" nonce="<?php echo CSP_NONCE; ?>"></script>

<?php
require_once HTML.'/footer.php';
?>
