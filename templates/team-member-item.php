<?php
$i18nKeys = ['DEMOTE', 'PROMOTE', 'REMOVE', 'REMOVE_MEMBER'];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
<div class="flex f_baseline f_nowrap f_space_around list_row pad_md w100" data-user-uuid="__USER_UUID__" data-user-role="__USER_ROLE__">
  <div class="list_item flex_1 txt_left">__MEMBER_NAME__</div>
  <div class="list_item flex_1 txt_left">__MEMBER_EMAIL__</div>
  <div class="list_item flex_half txt_center">__USER_ROLE__</div>
  <div class="list_item flex_half txt_center">
    <button class="btn btn_secondary btn_promote_member __PROMOTE_VISIBILITY_CLASS__" data-user-uuid="__USER_UUID__" aria-label="<?php echo htmlspecialchars($i18n['PROMOTE'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo htmlspecialchars($i18n['PROMOTE'], ENT_QUOTES, 'UTF-8'); ?>
    </button>
    <button class="btn btn_secondary btn_demote_member __DEMOTE_VISIBILITY_CLASS__" data-user-uuid="__USER_UUID__" aria-label="<?php echo htmlspecialchars($i18n['DEMOTE'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo htmlspecialchars($i18n['DEMOTE'], ENT_QUOTES, 'UTF-8'); ?>
    </button>
    <button class="btn btn_danger btn_remove_member" data-user-uuid="__USER_UUID__" aria-label="<?php echo htmlspecialchars($i18n['REMOVE_MEMBER'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo htmlspecialchars($i18n['REMOVE'], ENT_QUOTES, 'UTF-8'); ?>
    </button>
  </div>
</div>

