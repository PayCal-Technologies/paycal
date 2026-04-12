<html>
  <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
      <tr>
        <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
          <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_NEW_CONTACT_MESSAGE_TITLE'); ?></h1>
          <p style="margin:8px 0 0 0; color:#52606d;"><?php echo Strings::i18n('EMAIL_SUBMITTED_FROM'); ?> __PC_NAME__</p>
        </td>
      </tr>
      <tr>
        <td style="padding:24px;">
          <p style="margin:0 0 8px 0;"><strong><?php echo Strings::i18n('EMAIL_FROM_LABEL'); ?></strong> __SENDER_NAME__ &lt;__SENDER_EMAIL__&gt;</p>
          <p style="margin:0 0 8px 0;"><strong><?php echo Strings::i18n('EMAIL_TOPIC_LABEL'); ?></strong> __CONTACT_TOPIC__</p>
          <p style="margin:0 0 16px 0;"><strong><?php echo Strings::i18n('EMAIL_SUBJECT_LABEL'); ?></strong> __CONTACT_SUBJECT__</p>
          <div style="padding:16px; border:1px solid #bcccdc; border-radius:10px; background:#f8fafc;">
            <p style="margin:0 0 8px 0; font-weight:700;"><?php echo Strings::i18n('EMAIL_MESSAGE_LABEL'); ?></p>
            <div style="line-height:1.5;">__MESSAGE_CONTENT__</div>
          </div>
        </td>
      </tr>
    </table>
  </body>
</html>
