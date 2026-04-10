<?php declare(strict_types=1);

require_once '../config.php';

$i18nKeys = [
  'HELP_WORK_HOURS_FILLING_TITLE',
  'HELP_WORK_HOURS_GETTING_STARTED_TITLE',
  'HELP_WORK_HOURS_HOURS_TEXT',
  'HELP_WORK_HOURS_HOURS_TITLE',
  'HELP_WORK_HOURS_LIVING_OUT_TEXT',
  'HELP_WORK_HOURS_LIVING_OUT_TITLE',
  'HELP_WORK_HOURS_OT_TEXT',
  'HELP_WORK_HOURS_OT_TITLE',
  'HELP_WORK_HOURS_OVERVIEW_TEXT',
  'HELP_WORK_HOURS_OVERVIEW_TITLE',
  'HELP_WORK_HOURS_SAVING_TEXT_1',
  'HELP_WORK_HOURS_SAVING_TEXT_2',
  'HELP_WORK_HOURS_SAVING_TITLE',
  'HELP_WORK_HOURS_SITE_SELECTION_TEXT',
  'HELP_WORK_HOURS_SITE_SELECTION_TITLE',
  'HELP_WORK_HOURS_START_STEP_1_TEXT',
  'HELP_WORK_HOURS_START_STEP_1_TITLE',
  'HELP_WORK_HOURS_START_STEP_2_TEXT',
  'HELP_WORK_HOURS_START_STEP_2_TITLE',
  'HELP_WORK_HOURS_START_STEP_3_TEXT',
  'HELP_WORK_HOURS_START_STEP_3_TITLE',
  'HELP_WORK_HOURS_TIPS_TITLE',
  'HELP_WORK_HOURS_TIP_1',
  'HELP_WORK_HOURS_TIP_2',
  'HELP_WORK_HOURS_TIP_3',
  'HELP_WORK_HOURS_TIP_4',
  'HELP_WORK_HOURS_TIP_5',
  'HELP_WORK_HOURS_TITLE',
  'HELP_WORK_HOURS_TRAVEL_TEXT',
  'HELP_WORK_HOURS_TRAVEL_TITLE',
  'HELP_WORK_HOURS_TROUBLESHOOTING_TITLE',
  'HELP_WORK_HOURS_TROUBLE_1_TEXT',
  'HELP_WORK_HOURS_TROUBLE_1_TITLE',
  'HELP_WORK_HOURS_TROUBLE_2_TEXT',
  'HELP_WORK_HOURS_TROUBLE_2_TITLE',
  'HELP_WORK_HOURS_TROUBLE_3_TEXT',
  'HELP_WORK_HOURS_TROUBLE_3_TITLE',
  'HELP_WORK_HOURS_TROUBLE_4_TEXT',
  'HELP_WORK_HOURS_TROUBLE_4_TITLE',
  'HELP_WORK_HOURS_VALIDATION_1',
  'HELP_WORK_HOURS_VALIDATION_2',
  'HELP_WORK_HOURS_VALIDATION_3',
  'HELP_WORK_HOURS_VALIDATION_4',
  'HELP_WORK_HOURS_VALIDATION_INTRO',
  'HELP_WORK_HOURS_VALIDATION_NOTE',
  'HELP_WORK_HOURS_VALIDATION_TITLE',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

$currentPage = 'PAGE_HELP';
$pageTitle = $i18n['HELP_WORK_HOURS_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['HELP_WORK_HOURS_TITLE'];

require_once HTML.'/header.php';

?>

<section class='w100' role="region" aria-labelledby="work-hours-help-title">
  <h1 id="work-hours-help-title"><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TITLE'], ENT_QUOTES, 'UTF-8'); ?> - PayCal.app</h1>
</section>

<section class='panel w100 mar_sm pad_md'>
  <h2><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_OVERVIEW_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_OVERVIEW_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<section class='panel w100 mar_sm pad_md'>
  <h2><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_GETTING_STARTED_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_START_STEP_1_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_START_STEP_1_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_START_STEP_2_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_START_STEP_2_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_START_STEP_3_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_START_STEP_3_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<section class='panel w100 mar_sm pad_md'>
  <h2><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_FILLING_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_SITE_SELECTION_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_SITE_SELECTION_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_HOURS_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_HOURS_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_OT_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_OT_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_LIVING_OUT_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_LIVING_OUT_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TRAVEL_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TRAVEL_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<section class='panel w100 mar_sm pad_md'>
  <h2><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_SAVING_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_SAVING_TEXT_1'], ENT_QUOTES, 'UTF-8'); ?></p>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_SAVING_TEXT_2'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<section class='panel w100 mar_sm pad_md'>
  <h2><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_VALIDATION_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_VALIDATION_INTRO'], ENT_QUOTES, 'UTF-8'); ?></p>
  <ul>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_VALIDATION_1'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_VALIDATION_2'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_VALIDATION_3'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_VALIDATION_4'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
  </ul>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_VALIDATION_NOTE'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<section class='panel w100 mar_sm pad_md'>
  <h2><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TIPS_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
  <ul>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TIP_1'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TIP_2'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TIP_3'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TIP_4'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
    <li><strong><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TIP_5'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
  </ul>
</section>

<section class='panel w100 mar_sm pad_md'>
  <h2><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLESHOOTING_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLE_1_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLE_1_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLE_2_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLE_2_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLE_3_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLE_3_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
  
  <h3><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLE_4_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
  <p><?php echo htmlspecialchars($i18n['HELP_WORK_HOURS_TROUBLE_4_TEXT'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<?php

require_once HTML.'/footer.php';

?>
