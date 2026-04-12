<?php
$i18nKeys = ['CALENDAR_DAY_MENU_ARIA'];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
<div id="calendar_day_context_menu" class="hidden" aria-label="<?php echo htmlspecialchars($i18n['CALENDAR_DAY_MENU_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" role="menu" tabindex="-1">
  <span id="calendar_day_context_menu_head" class="flex pad_sm mar_xs border_inset"></span>
  <ul>
    <li class="context_menu_item" role="menuitem">
      <span>__COPY_LABEL__</span>__COPY_KEY__
    </li>
    <li class="context_menu_item" role="menuitem">
      <span>__PASTE_LABEL__</span>__PASTE_KEY__
    </li>
    <li class="context_menu_item" role="menuitem">
      <span>__OPEN_LABEL__</span>__OPEN_KEY__
    </li>
    <li class="context_menu_item" role="menuitem">
      <span>__DELETE_LABEL__</span>__DELETE_KEY__
    </li>
  </ul>
</div>
