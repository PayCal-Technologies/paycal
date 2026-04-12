<html>
  <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
      <tr>
        <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
          <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_CONFIRM_EMAIL_ADDRESS_TITLE'); ?></h1>
          <p style="margin:8px 0 0 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_WELCOME_TO'); ?> __PC_NAME__</p>
        </td>
      </tr>
      <tr>
        <td style="padding:24px;">
          <p style="margin:0 0 14px 0;"><?php echo Strings::i18n('EMAIL_HELLO'); ?> __USER_NAME__,</p>
          <p style="margin:0 0 16px 0;"><?php echo Strings::i18n('EMAIL_CHOOSE_OPTION_VERIFY_EMAIL'); ?></p>
          <div style="padding:16px; border:1px solid #bcccdc; border-radius:10px; background:#f8fafc; margin-bottom:14px;">
            <p style="margin:0 0 8px 0; font-weight:700;"><?php echo Strings::i18n('EMAIL_OPTION_1_ENTER_CODE'); ?></p>
            <div style="font-size:24px; letter-spacing:4px; font-weight:700;">__VERIFICATION_CODE__</div>
          </div>
          <div style="padding:16px; border:1px solid #bcccdc; border-radius:10px; background:#f8fafc; margin-bottom:16px;">
            <p style="margin:0 0 8px 0; font-weight:700;"><?php echo Strings::i18n('EMAIL_OPTION_2_OPEN_LINK'); ?></p>
            <a href="__VERIFICATION_URL__">__VERIFICATION_URL__</a>
          </div>
          <p style="margin:0 0 8px 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_LINK_EXPIRES_24_HOURS'); ?></p>
          <p style="margin:0; color:#52606d;"><?php echo Strings::i18n('EMAIL_AFTER_VERIFY_RECOVERY_KEY_SENT'); ?></p>
        </td>
      </tr>
    </table>
  </body>
</html>
