<?php
$i18nKeys = [
    'CONTACT_US',
    'FRONT_PAGE_BETA_NOTE',
    'FRONT_PAGE_CARD_1_BODY',
    'FRONT_PAGE_CARD_1_TITLE',
    'FRONT_PAGE_CARD_2_BODY',
    'FRONT_PAGE_CARD_2_TITLE',
    'FRONT_PAGE_CARD_3_BODY',
    'FRONT_PAGE_CARD_3_TITLE',
    'FRONT_PAGE_CARD_4_BODY',
    'FRONT_PAGE_CARD_4_TITLE',
    'FRONT_PAGE_CTA_PREFIX',
    'FRONT_PAGE_CTA_SUFFIX',
    'FRONT_PAGE_INTRO',
    'FRONT_PAGE_TITLE',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
    $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
        <h2><?php echo htmlspecialchars($i18n['FRONT_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars($i18n['FRONT_PAGE_INTRO'], ENT_QUOTES, 'UTF-8'); ?></p>
        
        <div class="row">
            <div class="w50 pad_right">
                <h3><span class='emoji'>&#x1F4B0;</span> <?php echo htmlspecialchars($i18n['FRONT_PAGE_CARD_1_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($i18n['FRONT_PAGE_CARD_1_BODY'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="w50 pad_left">
                <h3><span class='emoji'>&#x23F3;</span> <?php echo htmlspecialchars($i18n['FRONT_PAGE_CARD_2_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($i18n['FRONT_PAGE_CARD_2_BODY'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <div class="row">
            <div class="w50 pad_right">
                <h3><span class='emoji'>&#x23F3;</span> <?php echo htmlspecialchars($i18n['FRONT_PAGE_CARD_3_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($i18n['FRONT_PAGE_CARD_3_BODY'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="w50 pad_left">
                <h3><span class='emoji'>&#x1F310;</span> <?php echo htmlspecialchars($i18n['FRONT_PAGE_CARD_4_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($i18n['FRONT_PAGE_CARD_4_BODY'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <p><?php echo htmlspecialchars($i18n['FRONT_PAGE_BETA_NOTE'], ENT_QUOTES, 'UTF-8'); ?></p>
        
        <p><?php echo htmlspecialchars($i18n['FRONT_PAGE_CTA_PREFIX'], ENT_QUOTES, 'UTF-8'); ?> <a href='./contact/' aria-label='<?php echo htmlspecialchars($i18n['CONTACT_US'], ENT_QUOTES, 'UTF-8'); ?>'><?php echo htmlspecialchars($i18n['CONTACT_US'], ENT_QUOTES, 'UTF-8'); ?></a> <?php echo htmlspecialchars($i18n['FRONT_PAGE_CTA_SUFFIX'], ENT_QUOTES, 'UTF-8'); ?></p>
    

