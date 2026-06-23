<html>
  <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
      <tr>
        <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
          <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_ACCOUNT_RECOVERY_LINK_TITLE'); ?></h1>
          <p style="margin:8px 0 0 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_ACCOUNT_RECOVERY_LINK_SUBTITLE'); ?></p>
        </td>
      </tr>
      <tr>
        <td style="padding:24px;">
          <p style="margin:0 0 16px 0;"><?php echo Strings::i18n('EMAIL_HI'); ?> __USER_NAME__,</p>
          <p style="margin:0 0 16px 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_ACCOUNT_RECOVERY_LINK_EXPIRES_PREFIX'); ?> __EXPIRES_IN_MINUTES__ <?php echo Strings::i18n('EMAIL_MINUTES'); ?> <?php echo Strings::i18n('EMAIL_ACCOUNT_RECOVERY_LINK_EXPIRES_SUFFIX'); ?></p>
          <p style="margin:0 0 18px 0;">
            <a href="__RECOVERY_LINK__" style="display:inline-block; padding:12px 18px; border-radius:8px; text-decoration:none; background:#0d6efd; color:#ffffff; font-weight:700;"><?php echo Strings::i18n('EMAIL_ACCOUNT_RECOVERY_CREATE_NEW_PASSKEY'); ?></a>
          </p>
          <p style="margin:0; color:#52606d;"><?php echo Strings::i18n('EMAIL_IF_NOT_REQUESTED_IGNORE'); ?></p>
        </td>
      </tr>
    </table>
  </body>
</html>
