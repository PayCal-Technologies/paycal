<?php
$i18nKeys = ['DELETE', 'DELETE_ORGANIZATION', 'VIEW', 'VIEW_ORGANIZATION'];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
<div class="flex f_baseline f_nowrap f_space_around list_row pad_md w100" data-team-id="__TEAM_ID__">
  <div class="list_item w25 txt_left">__TEAM_NAME__</div>
  <div class="list_item w15 txt_center">__MEMBER_COUNT__</div>
  <div class="list_item w20 txt_center">__MANAGER_COUNT__</div>
  <div class="list_item w20 txt_center">__CREATED_DATE__</div>
  <div class="list_item w20 txt_center">
    <button class="btn btn_secondary btn_view_team" data-team-id="__TEAM_ID__" aria-label="<?php echo htmlspecialchars($i18n['VIEW_ORGANIZATION'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo htmlspecialchars($i18n['VIEW'], ENT_QUOTES, 'UTF-8'); ?>
    </button>
    <button class="btn btn_danger btn_delete_team __DELETE_VISIBILITY_CLASS__" data-team-id="__TEAM_ID__" aria-label="<?php echo htmlspecialchars($i18n['DELETE_ORGANIZATION'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo htmlspecialchars($i18n['DELETE'], ENT_QUOTES, 'UTF-8'); ?>
    </button>
  </div>
</div>

