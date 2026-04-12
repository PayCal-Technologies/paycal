<html>
  <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
      <tr>
        <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
          <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_CONFIRM_EMAIL_CHANGE_TITLE'); ?></h1>
          <p style="margin:8px 0 0 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_CODE_FOR_YOUR'); ?> __EMAIL_TYPE__ <?php echo Strings::i18n('EMAIL_ADDRESS_LOWER'); ?></p>
        </td>
      </tr>
      <tr>
        <td style="padding:24px;">
          <p style="margin:0 0 16px 0;"><?php echo Strings::i18n('EMAIL_HI'); ?> __USER_NAME__, <?php echo Strings::i18n('EMAIL_USE_CODE_APPROVE_EMAIL_CHANGE'); ?></p>
          <div style="padding:16px; border:1px solid #bcccdc; border-radius:10px; background:#f8fafc; text-align:center; margin-bottom:14px;">
            <div style="font-size:28px; letter-spacing:4px; font-weight:700;">__VERIFICATION_CODE__</div>
          </div>
          <p style="margin:0 0 8px 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_EXPIRES_IN'); ?> __EXPIRES_IN_MINUTES__ <?php echo Strings::i18n('EMAIL_MINUTES'); ?>.</p>
          <p style="margin:0; color:#52606d;"><?php echo Strings::i18n('EMAIL_TRANSACTION_LABEL'); ?> __TXN_ID__</p>
        </td>
      </tr>
    </table>
  </body>
</html>
