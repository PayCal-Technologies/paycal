<?php
$i18nKeys = [
  'AUTH_PRIVACY_LINK',
  'AUTH_SIGNIN_EMAIL_ARIA',
  'AUTH_SIGNIN_EMAIL_LABEL',
  'AUTH_SIGNIN_PASSKEY_BUTTON',
  'AUTH_SIGNIN_PASSKEY_STATUS',
  'AUTH_TAB_SIGNIN',
  'AUTH_TERMS_ACK_AND',
  'AUTH_TERMS_ACK_PREFIX',
  'AUTH_TERMS_LINK',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
<form id="signin-form" method="POST" action="/auth/" class="">
  <h2><?php echo htmlspecialchars($i18n['AUTH_TAB_SIGNIN'], ENT_QUOTES, 'UTF-8'); ?></h2>

  <section>
    <label for="email"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_EMAIL_LABEL'], ENT_QUOTES, 'UTF-8'); ?> <span aria-label="required">*</span></label>
    <input 
      type="email"
      id="email"
      name="email"
      value="__EMAIL_VALUE__"
      autocomplete="email"
      aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_EMAIL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>"
      autofocus
      required
    >
  </section>

  <section>
    <button id="signin-passkey" type="button" class="btn btn_primary" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
    <p id="signin-passkey-status" aria-live="polite"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_STATUS'], ENT_QUOTES, 'UTF-8'); ?></p>
  </section>
  <p><?php echo htmlspecialchars($i18n['AUTH_TERMS_ACK_PREFIX'], ENT_QUOTES, 'UTF-8'); ?> <a href="/policies/#terms"><?php echo htmlspecialchars($i18n['AUTH_TERMS_LINK'], ENT_QUOTES, 'UTF-8'); ?></a> <?php echo htmlspecialchars($i18n['AUTH_TERMS_ACK_AND'], ENT_QUOTES, 'UTF-8'); ?> <a href="/policies/#privacy"><?php echo htmlspecialchars($i18n['AUTH_PRIVACY_LINK'], ENT_QUOTES, 'UTF-8'); ?></a>.</p>
</form>
