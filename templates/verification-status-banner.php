<!-- Email Verification Status Banner -->
<?php
$i18nKeys = [
  'AUTH_VERIFICATION_BANNER_ALERT_FAILED',
  'AUTH_VERIFICATION_BANNER_BODY',
  'AUTH_VERIFICATION_BANNER_RESEND_BUTTON',
  'AUTH_VERIFICATION_BANNER_SEND_FAILED',
  'AUTH_VERIFICATION_BANNER_SENDING',
  'AUTH_VERIFICATION_BANNER_SENT',
  'AUTH_VERIFICATION_BANNER_TITLE',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
<div
  class="verification-status-banner hidden"
  data-email-verified="false"
  data-msg-sending="<?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_BANNER_SENDING'], ENT_QUOTES, 'UTF-8'); ?>"
  data-msg-sent="<?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_BANNER_SENT'], ENT_QUOTES, 'UTF-8'); ?>"
  data-msg-failed="<?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_BANNER_SEND_FAILED'], ENT_QUOTES, 'UTF-8'); ?>"
  data-msg-failed-alert="<?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_BANNER_ALERT_FAILED'], ENT_QUOTES, 'UTF-8'); ?>"
>
  <div class="verification-banner-content">
    <div class="banner-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
    </div>
    <div class="banner-text">
      <h4><?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_BANNER_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h4>
      <p><?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_BANNER_BODY'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="banner-actions">
      <button type="button" class="resend-verification-btn" id="resend-verification-btn">
        <?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_BANNER_RESEND_BUTTON'], ENT_QUOTES, 'UTF-8'); ?>
      </button>
    </div>
  </div>
</div>

<?php $verificationStatusBannerSriAttribute = \PayCal\Domain\Render::sriAttribute('js/signin/verification-status-banner.js'); ?>
<script src="/js/signin/verification-status-banner.js"<?php echo $verificationStatusBannerSriAttribute; ?>></script>
