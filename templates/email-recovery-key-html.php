<html>
  <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
      <tr>
        <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
          <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_YOUR_RECOVERY_KEY_PREFIX'); ?> __PC_NAME__ <?php echo Strings::i18n('EMAIL_RECOVERY_KEY_NOUN'); ?></h1>
          <p style="margin:8px 0 0 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_STORE_KEY_SECURE_LOCATION'); ?></p>
        </td>
      </tr>
      <tr>
        <td style="padding:24px;">
          <p style="margin:0 0 16px 0;"><?php echo Strings::i18n('EMAIL_HI'); ?> __USER_NAME__, <?php echo Strings::i18n('EMAIL_VERIFIED_SAVE_KEY_NOW'); ?></p>
          <div style="padding:16px; border:2px solid #486581; border-radius:10px; background:#f0f4f8; margin-bottom:14px;">
            <p style="margin:0 0 8px 0; font-weight:700;"><?php echo Strings::i18n('EMAIL_RECOVERY_KEY_LABEL'); ?></p>
            <code style="display:block; font-size:16px; font-weight:700; color:#102a43; word-break:break-word;">__RECOVERY_KEY__</code>
          </div>
          <div style="padding:14px; border:1px solid #d64545; border-radius:10px; background:#fff5f5; margin-bottom:16px;">
            <p style="margin:0; color:#8a1c1c;"><strong><?php echo Strings::i18n('EMAIL_IMPORTANT_LABEL'); ?></strong> <?php echo Strings::i18n('EMAIL_IF_LOSE_PASSKEYS_AND_KEY_NO_RECOVERY'); ?></p>
          </div>
          <p style="margin:0; color:#52606d;"><?php echo Strings::i18n('EMAIL_ACCOUNT_LABEL'); ?> __ACCOUNT_EMAIL__<br><span style="font-size:12px; color:#7b8794;">Source: __SOURCE_URL__</span><br><?php echo Strings::i18n('EMAIL_ISSUED_LABEL'); ?> __ISSUED_AT__<br><?php echo Strings::i18n('EMAIL_SUPPORT_TOKEN_LABEL'); ?> __SUPPORT_TOKEN__</p>
        </td>
      </tr>
    </table>
  </body>
</html>
