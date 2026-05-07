<?php
$translations = [
    'CONTACT_REASON' => 'Reason for contacting',
    'CONTACT_REASON_GENERAL' => 'General inquiry',
    'CONTACT_REASON_ACCOUNT' => 'Account or login issue',
    'CONTACT_REASON_BUG' => 'Report a bug',
    'CONTACT_REASON_BILLING' => 'Billing question',
    'CONTACT_REASON_FEATURE' => 'Request a feature',
    'CONTACT_HELP_TITLE' => 'Help us help you better',
    'CONTACT_HELP_INTRO' => 'Making your message clear helps us respond faster:',
    'CONTACT_HELP_TIP_1' => 'Be specific: Describe exactly what you\'re trying to do and what happens instead.',
    'CONTACT_HELP_TIP_2' => 'Include details: Check our optional context options below to share technical info.',
    'CONTACT_HELP_TIP_3' => 'One issue per message: If you have multiple concerns, send separate messages.',
    'CONTACT_HELP_CONTEXT' => 'Optional — Help us understand your context:',
    'CONTACT_CONTEXT_BROWSER' => 'Include my browser info',
    'CONTACT_CONTEXT_PAGE' => 'Include current page URL',
    'CONTACT_CONTEXT_LANGUAGE' => 'Include my language setting',
    'CONTACT_SLA_LABEL' => 'Response time:',
    'CONTACT_SLA_TEXT' => 'We typically respond to support messages within 24–48 hours during business days (Monday–Friday, 9am–5pm ET). Thank you for your patience.',
    'CONTACT_SUCCESS_TITLE' => 'Message sent!',
    'CONTACT_SUCCESS_SENT_AT' => 'Sent at',
    'CONTACT_SUCCESS_NOTE' => 'We\'ve received your message and will respond as soon as possible.',
    'CONTACT_SEND_ANOTHER' => 'Send another',
    'PLEASE_SELECT' => 'Please select',
];

$langs = ['en', 'fr', 'de', 'es', 'it', 'nl', 'pt', 'hi', 'tl', 'tr'];
foreach ($langs as $lang) {
    $file = __DIR__ . '/strings/' . $lang . '.txt';
    $content = file_get_contents($file);
    
    foreach ($translations as $key => $value) {
        if (strpos($content, $key) === false) {
            $content .= "\n" . $key . " " . $value;
        }
    }
    
    file_put_contents($file, $content);
}

echo "✓ Translation keys added to all 10 language files\n";
