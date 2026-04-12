<html>
  <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
      <tr>
        <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
          <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_ORG_ACCESS_INVITE_TITLE'); ?></h1>
          <p style="margin:8px 0 0 0; color:#52606d;">__PC_NAME__</p>
        </td>
      </tr>
      <tr>
        <td style="padding:24px;">
          <p style="margin:0 0 14px 0;"><strong>__INVITER_NAME__</strong> <?php echo Strings::i18n('EMAIL_ORG_INVITED_TO_COLLABORATE_IN'); ?> <strong>__ORGANIZATION_NAME__</strong>.</p>
          <p style="margin:0 0 10px 0;"><?php echo Strings::i18n('EMAIL_REQUESTED_PERMISSION_SCOPES'); ?></p>
          <div style="padding:12px; border:1px solid #bcccdc; border-radius:8px; background:#f8fafc; margin-bottom:14px;">
            <code>__SCOPE_LIST__</code>
          </div>
          <p style="margin:0 0 10px 0;"><?php echo Strings::i18n('EMAIL_TO_ACCEPT_INVITE_OPEN'); ?></p>
          <p style="margin:0 0 14px 0;"><a href="__ACCEPT_URL__">__ACCEPT_URL__</a></p>
          <p style="margin:0; color:#52606d;"><?php echo Strings::i18n('EMAIL_UNEXPECTED_INVITE_IGNORE'); ?></p>
        </td>
      </tr>
    </table>
  </body>
</html>
