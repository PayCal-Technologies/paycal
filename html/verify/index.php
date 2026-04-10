<?php declare(strict_types=1);

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Strings;

require_once '../config.php';

$i18nKeys = [
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $verificationCode = InputSanitizer::postString('verification_code');
  $code = strtoupper(str_replace('-', '', $verificationCode));
  if (strlen($code) !== SystemConfig::PC_VERIFICATION_LENGTH) {
    $error = $i18n['VERIFY_ERROR_INVALID_CODE_LENGTH'];
  } else {
    header('Location: /auth/verify-email/?code=' . urlencode($code));
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($i18n['VERIFY_ACCOUNT_META_TITLE'], ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/auth/verify/">
</head>
<body>
  <div class="container">
    <h1><?php echo htmlspecialchars($i18n['VERIFY_ACCOUNT_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php if ($error) { ?>
      <p class="error" role="alert"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <form action="" method="post" autocomplete="off" aria-label="<?php echo htmlspecialchars($i18n['VERIFY_FORM_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
      <label for="verification_code" class="verification-label"><?php echo htmlspecialchars($i18n['VERIFY_CODE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label><br>
      <input type="text" id="verification_code" name="verification_code" maxlength="7" pattern="[A-Z]{3}-[A-Z]{3}" class="verification-input" inputmode="text" aria-required="true" aria-label="<?php echo htmlspecialchars($i18n['VERIFY_CODE_INPUT_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" required autofocus placeholder="<?php echo htmlspecialchars($i18n['VERIFY_CODE_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>" data-verify-code-format="true">
      <button type="submit"><?php echo htmlspecialchars($i18n['VERIFY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
    </form>
    <p><a href="/auth/?auth_tab=register"><?php echo htmlspecialchars($i18n['VERIFY_BACK_TO_REGISTER'], ENT_QUOTES, 'UTF-8'); ?></a></p>
  </div>
  <?php $verifyCodeInputSriAttribute = \PayCal\Domain\Render::sriAttribute('js/signin/verify-code-input.js'); ?>
  <script src="/js/signin/verify-code-input.js"<?php echo $verifyCodeInputSriAttribute; ?>></script>
</body>
</html>
