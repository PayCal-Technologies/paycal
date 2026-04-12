<html>
  <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
      <tr>
        <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
          <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_ADDRESS_UPDATED_TITLE'); ?></h1>
          <p style="margin:8px 0 0 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_SECURITY_NOTIFICATION_FROM'); ?> __PC_NAME__</p>
        </td>
      </tr>
      <tr>
        <td style="padding:24px;">
          <p style="margin:0 0 12px 0;"><?php echo Strings::i18n('EMAIL_HI'); ?> __USER_NAME__,</p>
          <p style="margin:0 0 12px 0;"><?php echo Strings::i18n('EMAIL_ACCOUNT_EMAIL_WAS'); ?> __CONTEXT_LABEL__ <strong>__OTHER_EMAIL__</strong>.</p>
          <p style="margin:0; color:#52606d;"><?php echo Strings::i18n('EMAIL_CHANGE_TIME_LABEL'); ?> __CHANGE_TIME__</p>
        </td>
      </tr>
    </table>
  </body>
</html>