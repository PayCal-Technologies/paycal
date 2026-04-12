<?php
$i18nKeys = ['HOURS', 'LIVING_OUT_ALLOWANCE', 'TRAVEL_HOURS'];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
  <template id='template_work_row'>
      <div class='list_item list_item_primary pad_sm'></div>
        <input class='list_item list_item_secondary pad_sm' type='number' name='hours'                value='' placeholder='<?php echo htmlspecialchars($i18n['HOURS'], ENT_QUOTES, 'UTF-8'); ?>' autofocus aria-required="true" aria-label="<?php echo htmlspecialchars($i18n['HOURS'], ENT_QUOTES, 'UTF-8'); ?>">
        <input class='list_item list_item_secondary pad_sm' type='number' name='living_out_allowance' value='' placeholder='<?php echo htmlspecialchars($i18n['LIVING_OUT_ALLOWANCE'], ENT_QUOTES, 'UTF-8'); ?>' aria-required="true" aria-label="<?php echo htmlspecialchars($i18n['LIVING_OUT_ALLOWANCE'], ENT_QUOTES, 'UTF-8'); ?>">
        <input class='list_item list_item_secondary pad_sm' type='number' name='travel_hours'         value='' placeholder='<?php echo htmlspecialchars($i18n['TRAVEL_HOURS'], ENT_QUOTES, 'UTF-8'); ?>' aria-required="true" aria-label="<?php echo htmlspecialchars($i18n['TRAVEL_HOURS'], ENT_QUOTES, 'UTF-8'); ?>">
  </template>

