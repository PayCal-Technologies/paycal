<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<!-- Team member earnings breakdown dialog -->
<dialog id="earnings_team_member_dialog"
        class="dialog earnings_team_member_dialog"
        data-dialog-close-on-backdrop="true"
  aria-labelledby="earnings_team_member_dialog_title"
  aria-describedby="earnings_team_member_dialog_body"
  aria-modal="true">
  <section class="modal_header">
    <button type="button" class="btn btn_close" data-dialog-close="earnings_team_member_dialog" aria-label="<?php echo htmlspecialchars($i18n['CLOSE'], ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
    <h2 id="earnings_team_member_dialog_title" class="modal_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_MEMBER_EARNINGS', 'Member Earnings'), ENT_QUOTES, 'UTF-8'); ?></h2>
  </section>
  <section class="modal_content earnings_team_member_dialog_content">
    <div id="earnings_team_member_dialog_body" class="earnings_team_breakdown">
    </div>
  </section>
  <section class="modal_footer">
    <button type="button" class="btn btn_secondary" data-dialog-close="earnings_team_member_dialog"><?php echo htmlspecialchars($i18n['CLOSE'], ENT_QUOTES, 'UTF-8'); ?></button>
  </section>
</dialog>
