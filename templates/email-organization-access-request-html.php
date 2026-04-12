<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="font-family:Arial,sans-serif; background:#f4f6f8; padding:24px;">
  <tr>
    <td align="center">
      <table width="620" cellpadding="0" cellspacing="0" role="presentation" style="max-width:620px; background:#ffffff; border:1px solid #d8dee4; border-radius:8px; overflow:hidden;">
        <tr>
          <td style="background:#1f2a37; color:#ffffff; padding:16px 20px;">
            <h1 style="margin:0; font-size:20px;"><?php echo Strings::i18n('EMAIL_ORG_ACCESS_REQUEST_TITLE'); ?></h1>
          </td>
        </tr>
        <tr>
          <td style="padding:20px; color:#1f2a37; font-size:15px; line-height:1.55;">
            <p style="margin:0 0 12px 0;"><?php echo Strings::i18n('EMAIL_ORG_ACCESS_REQUESTED_TO'); ?> <strong>__ORGANIZATION_NAME__</strong>.</p>
            <p style="margin:0 0 12px 0;"><strong><?php echo Strings::i18n('EMAIL_REQUESTER_LABEL'); ?></strong> __REQUESTER_NAME__</p>
            <p style="margin:0 0 12px 0;"><strong><?php echo Strings::i18n('EMAIL_EMAIL_LABEL'); ?></strong> __REQUESTER_EMAIL__</p>
            <p style="margin:0 0 12px 0;"><strong><?php echo Strings::i18n('EMAIL_REQUEST_ID_LABEL'); ?></strong> __REQUEST_ID__</p>
            <p style="margin:0 0 16px 0;"><strong><?php echo Strings::i18n('EMAIL_REQUESTED_AT_LABEL'); ?></strong> __REQUESTED_AT_UTC__</p>
            <p style="margin:0 0 10px 0;"><?php echo Strings::i18n('EMAIL_REVIEW_PENDING_REQUESTS_HERE'); ?></p>
            <p style="margin:0 0 12px 0;"><a href="__REVIEW_URL__" style="color:#0b63ce; text-decoration:none;">__REVIEW_URL__</a></p>
            <p style="margin:0; color:#52606d; font-size:13px;"><?php echo Strings::i18n('EMAIL_ORG_ACCESS_UNKNOWN_NOTICE'); ?></p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
