<?php
$i18nKeys = ['CONTACT_US','FAQ_A1','FAQ_A2','FAQ_A3','FAQ_A4','FAQ_A5','FAQ_A6','FAQ_A7','FAQ_CONTACT_TEXT','FAQ_Q1','FAQ_Q2','FAQ_Q3','FAQ_Q4','FAQ_Q5','FAQ_Q6','FAQ_Q7','FAQ_TITLE','LAST_UPDATED'];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
<div class='pad_wide'>
<h1><?php echo htmlspecialchars($i18n['FAQ_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
<p><?php echo htmlspecialchars($i18n['LAST_UPDATED'], ENT_QUOTES, 'UTF-8'); ?> 22 november 2023.</p>
<h2><?php echo htmlspecialchars($i18n['FAQ_Q1'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p><?php echo htmlspecialchars($i18n['FAQ_A1'], ENT_QUOTES, 'UTF-8'); ?></p>
<h2><?php echo htmlspecialchars($i18n['FAQ_Q2'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p><?php echo htmlspecialchars($i18n['FAQ_A2'], ENT_QUOTES, 'UTF-8'); ?></p>
<h2><?php echo htmlspecialchars($i18n['FAQ_Q3'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p><?php echo htmlspecialchars($i18n['FAQ_A3'], ENT_QUOTES, 'UTF-8'); ?></p>
<h2><?php echo htmlspecialchars($i18n['FAQ_Q4'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p><?php echo htmlspecialchars($i18n['FAQ_A4'], ENT_QUOTES, 'UTF-8'); ?></p>
<h2><?php echo htmlspecialchars($i18n['FAQ_Q5'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p><?php echo htmlspecialchars($i18n['FAQ_A5'], ENT_QUOTES, 'UTF-8'); ?></p>
<h2><?php echo htmlspecialchars($i18n['FAQ_Q6'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p><?php echo htmlspecialchars($i18n['FAQ_A6'], ENT_QUOTES, 'UTF-8'); ?></p>
<h2><?php echo htmlspecialchars($i18n['FAQ_Q7'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p><?php echo htmlspecialchars($i18n['FAQ_A7'], ENT_QUOTES, 'UTF-8'); ?></p>
<h3><?php echo htmlspecialchars($i18n['CONTACT_US'], ENT_QUOTES, 'UTF-8'); ?></h3>
<p><?php echo htmlspecialchars($i18n['FAQ_CONTACT_TEXT'], ENT_QUOTES, 'UTF-8'); ?> <a href="mailto:<?php echo PC_EMAIL_REPLYTO; ?>"><?php echo PC_EMAIL_REPLYTO; ?></a>.</p>
</div>
