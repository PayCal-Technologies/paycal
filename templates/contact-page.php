<?php declare(strict_types=1);

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Strings;

/** @var string $formStatus */
/** @var string $formStatusType */
/** @var array<string, string> $formFieldErrors */
/** @var array<string, string> $formFieldValues */
/** @var int $contactCooldownRemaining */
/** @var int $contactCooldownDuration */
/** @var string $contactFormToken */

$i18nKeys = [
  'CONTACT_CONTEXT_BROWSER',
  'CONTACT_CONTEXT_LANGUAGE',
  'CONTACT_CONTEXT_PAGE',
  'CONTACT_DECK_INTRO',
  'CONTACT_DEFAULT_STATUS',
  'CONTACT_DETAILS_ARIA',
  'CONTACT_DIAGNOSTICS_INTRO',
  'CONTACT_DIAGNOSTICS_TITLE',
  'CONTACT_FORM_BASICS_ARIA',
  'CONTACT_FORM_NOTES_ARIA',
  'CONTACT_HELP_INTRO',
  'CONTACT_HELP_TIP_1',
  'CONTACT_HELP_TIP_2',
  'CONTACT_HELP_TIP_3',
  'CONTACT_HELP_TITLE',
  'CONTACT_MESSAGE_PLACEHOLDER',
  'CONTACT_PAGE_ARIA',
  'CONTACT_REASON',
  'CONTACT_REASON_ACCOUNT',
  'CONTACT_REASON_BUG',
  'CONTACT_REASON_FEATURE',
  'CONTACT_REASON_GENERAL',
  'CONTACT_SEND_ANOTHER',
  'CONTACT_SLA_LABEL',
  'CONTACT_SLA_TEXT',
  'CONTACT_SUCCESS_NOTE',
  'CONTACT_SUCCESS_SENT_AT',
  'CONTACT_SUCCESS_TITLE',
  'CONTACT_US',
  'EMAIL_SUBJECT',
  'MESSAGE',
  'PLEASE_SELECT',
  'SEND_EMAIL',
  'YOUR_EMAIL',
  'YOUR_NAME',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

$contactStatusText = $formStatus !== ''
  ? $formStatus
  : $i18n['CONTACT_DEFAULT_STATUS'];

$contactStatusRole = $formStatusType === 'error' ? 'alert' : 'status';
$contactStatusLive = $formStatusType === 'error' ? 'assertive' : 'polite';

$nameValue = htmlspecialchars((string) ($formFieldValues['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$emailValue = htmlspecialchars((string) ($formFieldValues['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$subjectValue = htmlspecialchars((string) ($formFieldValues['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
$messageValue = htmlspecialchars((string) ($formFieldValues['message'] ?? ''), ENT_QUOTES, 'UTF-8');
$reasonValue = htmlspecialchars((string) ($formFieldValues['reason'] ?? ''), ENT_QUOTES, 'UTF-8');

$nameError = (string) ($formFieldErrors['name'] ?? '');
$emailError = (string) ($formFieldErrors['email'] ?? '');
$subjectError = (string) ($formFieldErrors['subject'] ?? '');
$messageError = (string) ($formFieldErrors['message'] ?? '');
$reasonError = (string) ($formFieldErrors['reason'] ?? '');

$hasError = static fn (string $key): bool => isset($formFieldErrors[$key]) && $formFieldErrors[$key] !== '';
?>

<article class="article doc-article contact-page" aria-label="<?php echo htmlspecialchars($i18n['CONTACT_PAGE_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
  <header class="doc-article-header pad_md">
    <h1><?php echo htmlspecialchars($i18n['CONTACT_US'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars($i18n['CONTACT_DECK_INTRO'], ENT_QUOTES, 'UTF-8'); ?> <a href="mailto:info@paycal.app">info@paycal.app</a></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section pad_md">
      <div class="contact-status-slot" aria-live="polite">
        <div
          id="contact_status"
          class="contact-status contact-status--<?php echo htmlspecialchars($formStatusType, ENT_QUOTES, 'UTF-8'); ?>"
          role="<?php echo $contactStatusRole; ?>"
          aria-live="<?php echo $contactStatusLive; ?>"
          aria-atomic="true"
          tabindex="-1"
          data-toast-region="contact"
          <?php if ($formStatus === '') { ?>hidden<?php } ?>
        ><?php echo htmlspecialchars($contactStatusText, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div id="contact_success_card" class="contact-success-card" hidden>
        <div class="contact-success-icon">✓</div>
        <div class="contact-success-content">
          <h3 class="contact-success-title"><?php echo htmlspecialchars($i18n['CONTACT_SUCCESS_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="contact-success-time"><?php echo htmlspecialchars($i18n['CONTACT_SUCCESS_SENT_AT'], ENT_QUOTES, 'UTF-8'); ?> <span id="contact_sent_time">-</span></p>
          <p class="contact-success-note"><?php echo htmlspecialchars($i18n['CONTACT_SUCCESS_NOTE'], ENT_QUOTES, 'UTF-8'); ?></p>
          <p id="contact_success_cooldown" class="contact-success-cooldown" aria-live="polite" aria-atomic="true" hidden></p>
        </div>
        <button type="button" id="contact_send_another" class="btn btn_secondary"><?php echo htmlspecialchars($i18n['CONTACT_SEND_ANOTHER'], ENT_QUOTES, 'UTF-8'); ?></button>
      </div>

      <form
        id="contact_form"
        class="contact-form pad_md"
        action="<?php echo Environment::appURL('contact/'); ?>"
        method="POST"
        novalidate
        aria-describedby="contact_status"
        data-cooldown-remaining="<?php echo (int) $contactCooldownRemaining; ?>"
        data-cooldown-duration="<?php echo (int) $contactCooldownDuration; ?>"
      >
        <input type="hidden" name="pc_method" value="xhr">
        <input type="hidden" name="contact_website" value="">
        <input type="hidden" name="contact_form_time" value="0">
        <input type="hidden" name="contact_form_token" value="<?php echo htmlspecialchars($contactFormToken, ENT_QUOTES, 'UTF-8'); ?>">

        <section class="contact-form-section contact-form-section--top" aria-label="<?php echo htmlspecialchars($i18n['CONTACT_FORM_BASICS_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="contact-form-top-grid">
            <div class="contact-field">
              <label for="name"><?php echo htmlspecialchars($i18n['YOUR_NAME'], ENT_QUOTES, 'UTF-8'); ?></label>
              <input
                type="text"
                id="name"
                name="name"
                value="<?php echo $nameValue; ?>"
                autofocus
                autocomplete="name"
                required
                maxlength="120"
                <?php if ($hasError('name')) { ?>aria-invalid="true" aria-describedby="name_error"<?php } ?>
              >
              <p id="name_error" class="contact-field-error"<?php if (!$hasError('name')) { ?> hidden<?php } ?>><?php echo htmlspecialchars($nameError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="contact-field">
              <label for="email"><?php echo htmlspecialchars($i18n['YOUR_EMAIL'], ENT_QUOTES, 'UTF-8'); ?></label>
              <input
                type="email"
                id="email"
                name="email"
                value="<?php echo $emailValue; ?>"
                autocomplete="email"
                inputmode="email"
                required
                maxlength="190"
                <?php if ($hasError('email')) { ?>aria-invalid="true" aria-describedby="email_error"<?php } ?>
              >
              <p id="email_error" class="contact-field-error"<?php if (!$hasError('email')) { ?> hidden<?php } ?>><?php echo htmlspecialchars($emailError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="contact-field contact-field--full">
              <label for="subject"><?php echo htmlspecialchars($i18n['EMAIL_SUBJECT'], ENT_QUOTES, 'UTF-8'); ?></label>
              <input
                type="text"
                id="subject"
                name="subject"
                value="<?php echo $subjectValue; ?>"
                required
                maxlength="180"
                <?php if ($hasError('subject')) { ?>aria-invalid="true" aria-describedby="subject_error"<?php } ?>
              >
              <p id="subject_error" class="contact-field-error"<?php if (!$hasError('subject')) { ?> hidden<?php } ?>><?php echo htmlspecialchars($subjectError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="contact-field contact-field--full">
              <label for="reason"><?php echo htmlspecialchars($i18n['CONTACT_REASON'], ENT_QUOTES, 'UTF-8'); ?></label>
              <select
                id="reason"
                name="reason"
                required
                <?php if ($hasError('reason')) { ?>aria-invalid="true" aria-describedby="reason_error"<?php } ?>
              >
                <option value="">- <?php echo htmlspecialchars($i18n['PLEASE_SELECT'], ENT_QUOTES, 'UTF-8'); ?> -</option>
                <option value="general" <?php if ($reasonValue === 'general') { ?>selected<?php } ?>><?php echo htmlspecialchars($i18n['CONTACT_REASON_GENERAL'], ENT_QUOTES, 'UTF-8'); ?></option>
                <option value="account" <?php if ($reasonValue === 'account') { ?>selected<?php } ?>><?php echo htmlspecialchars($i18n['CONTACT_REASON_ACCOUNT'], ENT_QUOTES, 'UTF-8'); ?></option>
                <option value="bug" <?php if ($reasonValue === 'bug') { ?>selected<?php } ?>><?php echo htmlspecialchars($i18n['CONTACT_REASON_BUG'], ENT_QUOTES, 'UTF-8'); ?></option>
                <option value="feature" <?php if ($reasonValue === 'feature') { ?>selected<?php } ?>><?php echo htmlspecialchars($i18n['CONTACT_REASON_FEATURE'], ENT_QUOTES, 'UTF-8'); ?></option>
              </select>
              <p id="reason_error" class="contact-field-error"<?php if (!$hasError('reason')) { ?> hidden<?php } ?>><?php echo htmlspecialchars($reasonError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </div>
        </section>

        <section class="contact-form-section contact-form-section--bottom" aria-label="<?php echo htmlspecialchars($i18n['CONTACT_FORM_NOTES_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="contact-form-bottom-grid">
            <div class="contact-field contact-field--notes">
              <label for="message"><?php echo htmlspecialchars($i18n['MESSAGE'], ENT_QUOTES, 'UTF-8'); ?></label>
              <textarea
                id="message"
                name="message"
                rows="16"
                required
                maxlength="6000"
                placeholder="<?php echo htmlspecialchars($i18n['CONTACT_MESSAGE_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>"
                <?php if ($hasError('message')) { ?>aria-invalid="true" aria-describedby="message_error"<?php } ?>
              ><?php echo $messageValue; ?></textarea>
              <div class="contact-field-inline-actions">
                <p id="message_count" class="contact-field-count" aria-live="polite" aria-atomic="true"><?php echo strlen($messageValue); ?>/6000</p>
              </div>
              <p id="message_error" class="contact-field-error"<?php if (!$hasError('message')) { ?> hidden<?php } ?>><?php echo htmlspecialchars($messageError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <aside class="contact-details-panel" aria-label="<?php echo htmlspecialchars($i18n['CONTACT_DETAILS_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
              <p id="contact-sla-info" class="contact-sla-info contact-sla-info--center"><strong><?php echo htmlspecialchars($i18n['CONTACT_SLA_LABEL'], ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($i18n['CONTACT_SLA_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
              <div class="contact-help-divider"></div>
              <div class="contact-details-split">
                <div class="contact-guide-block">
                  <h2 class="contact-help-section-title"><?php echo htmlspecialchars($i18n['CONTACT_HELP_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
                  <p class="contact-help-desc"><?php echo htmlspecialchars($i18n['CONTACT_HELP_INTRO'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <ul class="contact-help-tips">
                    <li><?php echo htmlspecialchars($i18n['CONTACT_HELP_TIP_1'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><?php echo htmlspecialchars($i18n['CONTACT_HELP_TIP_2'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><?php echo htmlspecialchars($i18n['CONTACT_HELP_TIP_3'], ENT_QUOTES, 'UTF-8'); ?></li>
                  </ul>
                </div>

                <div class="contact-optional-block">
                  <h2 class="contact-help-section-title contact-help-section-title--diagnostics"><?php echo htmlspecialchars($i18n['CONTACT_DIAGNOSTICS_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
                  <p class="contact-help-desc"><?php echo htmlspecialchars($i18n['CONTACT_DIAGNOSTICS_INTRO'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <div class="contact-help-chips">
                    <label class="contact-chip">
                      <input type="checkbox" name="include_browser_device" value="1" class="contact-chip-input">
                      <span class="contact-chip-label"><?php echo htmlspecialchars($i18n['CONTACT_CONTEXT_BROWSER'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                    <label class="contact-chip">
                      <input type="checkbox" name="include_ip_address" value="1" class="contact-chip-input">
                      <span class="contact-chip-label"><?php echo htmlspecialchars($i18n['CONTACT_CONTEXT_PAGE'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                    <label class="contact-chip">
                      <input type="checkbox" name="include_language_region" value="1" class="contact-chip-input">
                      <span class="contact-chip-label"><?php echo htmlspecialchars($i18n['CONTACT_CONTEXT_LANGUAGE'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                  </div>
                </div>
              </div>
            </aside>

            <div class="contact-actions contact-form-footer">
              <button
                type="submit"
                id="submit"
                class="btn btn_primary"
                data-base-label="<?php echo htmlspecialchars($i18n['SEND_EMAIL'], ENT_QUOTES, 'UTF-8'); ?>"
                aria-describedby="contact_status contact_cooldown_hint"
              ><?php echo htmlspecialchars($i18n['SEND_EMAIL'], ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
            <p id="contact_cooldown_hint" class="contact-cooldown-hint" aria-live="polite" aria-atomic="true" hidden></p>
          </div>
        </section>
      </form>
    </section>
  </section>
</article>
