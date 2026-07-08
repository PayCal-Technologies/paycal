<?php declare(strict_types=1);

use PayCal\Domain\Strings;

require_once '../config.php';

$i18nKeys = [
  'SKIP_TO_CONTENT',
  'VERIFY_ACCOUNT_HEADING',
  'VERIFY_ACCOUNT_META_TITLE',
  'VERIFY_BACK_TO_REGISTER',
  'VERIFY_BUTTON',
  'VERIFY_CODE_INPUT_ARIA',
  'VERIFY_CODE_LABEL',
  'VERIFY_CODE_PLACEHOLDER',
  'VERIFY_ERROR_INVALID_CODE_LENGTH',
  'VERIFY_FORM_ARIA',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

$error = '';
$pageLanguage = defined('USER_LANGUAGE') ? (string) USER_LANGUAGE : 'en';
$cssVersion = \PayCal\Domain\Environment::appVersion();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(str_replace('_', '-', $pageLanguage), ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($i18n['VERIFY_ACCOUNT_META_TITLE'], ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="/css/utilities/?v=<?php echo htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="stylesheet" href="/css/auth/verify/">
</head>
<body>
  <a id="skip_to_content" class="skip_link" href="#main" accesskey="0" aria-keyshortcuts="Alt+0"><?php echo htmlspecialchars($i18n['SKIP_TO_CONTENT'], ENT_QUOTES, 'UTF-8'); ?></a>
  <main id="main" tabindex="-1" class="container">
    <h1><?php echo htmlspecialchars($i18n['VERIFY_ACCOUNT_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php if ($error) { ?>
      <p class="error" role="alert"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <form action="/api/v1/auth/verify-email" method="post" autocomplete="off" aria-label="<?php echo htmlspecialchars($i18n['VERIFY_FORM_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
      <label for="verification_code" class="verification-label"><?php echo htmlspecialchars($i18n['VERIFY_CODE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label><br>
      <input type="text" id="verification_code" name="verification_code" maxlength="7" pattern="[A-Z]{3}-[A-Z]{3}" class="verification-input" inputmode="text" aria-required="true" aria-label="<?php echo htmlspecialchars($i18n['VERIFY_CODE_INPUT_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" required autofocus placeholder="<?php echo htmlspecialchars($i18n['VERIFY_CODE_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>" data-verify-code-format="true">
      <button type="submit"><?php echo htmlspecialchars($i18n['VERIFY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
    </form>
    <p><a href="/auth/signup/"><?php echo htmlspecialchars($i18n['VERIFY_BACK_TO_REGISTER'], ENT_QUOTES, 'UTF-8'); ?></a></p>
  </main>
  <?php $verifyCodeInputSriAttribute = \PayCal\Domain\Render::sriAttribute('js/signin/verify-code-input.js'); ?>
  <script type="module" src="/js/signin/verify-code-input.js?v=<?php echo htmlspecialchars(\PayCal\Domain\Render::assetCacheVersion('js/signin/verify-code-input.js'), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $verifyCodeInputSriAttribute; ?>></script>
</body>
</html>
