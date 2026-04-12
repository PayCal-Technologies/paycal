<html>
  <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
      <tr>
        <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
          <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_VERIFY_YOUR_EMAIL_TITLE'); ?></h1>
          <p style="margin:8px 0 0 0; color:#52606d;">__PC_NAME__ <?php echo Strings::i18n('EMAIL_ACCOUNT_SECURITY_SUFFIX'); ?></p>
        </td>
      </tr>
      <tr>
        <td style="padding:24px;">
          <p style="margin:0 0 16px 0;"><?php echo Strings::i18n('EMAIL_USE_VERIFICATION_CODE_TO_CONTINUE'); ?></p>
          <div style="padding:16px; border:1px solid #bcccdc; border-radius:10px; background:#f8fafc; text-align:center;">
            <div style="font-size:28px; letter-spacing:4px; font-weight:700;">__VERIFICATION_CODE__</div>
          </div>
          <p style="margin:16px 0 0 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_IF_NOT_REQUESTED_IGNORE'); ?></p>
          <p style="margin:14px 0 0 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_ACCOUNT_LABEL'); ?> __ACCOUNT_EMAIL__<br><span style="font-size:12px; color:#7b8794;">Source: __SOURCE_URL__</span><br><?php echo Strings::i18n('EMAIL_ISSUED_LABEL'); ?> __ISSUED_AT__<br><?php echo Strings::i18n('EMAIL_SUPPORT_TOKEN_LABEL'); ?> __SUPPORT_TOKEN__</p>
        </td>
      </tr>
    </table>
  </body>
</html>

