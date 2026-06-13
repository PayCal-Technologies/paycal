<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/i18n-data.php';

$sitesJsI18n = [];
foreach (sites_js_i18n_keys() as $sitesJsKey) {
  $sitesJsI18n[$sitesJsKey] = sites_js_i18n($sitesJsKey);
}
?>
const SITES_T = <?php echo json_encode($sitesJsI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const sitesFormatMessage = (template, values = {}) => String(template || '').replace(/\{(\w+)\}/g, (match, key) => (
  Object.prototype.hasOwnProperty.call(values, key) ? String(values[key]) : match
));
const sitesPluralLabel = (count, singular, plural) => (
  Number(count) === 1 ? singular : plural
);
