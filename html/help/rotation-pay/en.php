<?php declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\RotationPayExplainer;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_rotation_i18n') === false) {
  /**
   * Return localized strings for this page.
   */
  function help_rotation_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_rotation_i18n('HELP_ROTATION_PAY_TITLE') . ' - [' . help_rotation_i18n('SITE_NAME') . ']';
$pageLabel = help_rotation_i18n('HELP_ROTATION_PAY_TITLE');
$pageMetaDescription = help_rotation_i18n('HELP_ROTATION_PAY_DESCRIPTION');
$pageMetaDescriptionLong = help_rotation_i18n('HELP_ROTATION_PAY_DECK');
$example = RotationPayExplainer::comparison();

require_once HTML . '/header.php';

$money = static function (int $cents): string {
  return '$' . number_format($cents / 100, 2, '.', ',');
};
$h = static function (string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $h(help_rotation_i18n('HELP_BREADCRUMB_ARIA')); ?>">
    <a href="/"><?php echo $h(help_rotation_i18n('HOME')); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo $h(help_rotation_i18n('HELP_CENTER_TITLE')); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_TITLE')); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_TITLE')); ?></h1>
    <p class="deck"><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_DECK')); ?></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_EXAMPLE_TITLE')); ?></h2>
      <p><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_EXAMPLE_INTRO')); ?></p>
      <ul>
        <?php for ($assumption = 1; $assumption <= 9; $assumption++) { ?>
          <li><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_ASSUMPTION_' . $assumption)); ?></li>
        <?php } ?>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_RESULTS_TITLE')); ?></h2>
      <table class="doc-table">
        <caption class="visually_hidden"><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_RESULTS_CAPTION')); ?></caption>
        <thead>
          <tr>
            <th scope="col"><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_COL_ITEM')); ?></th>
            <th scope="col">14/7</th>
            <th scope="col">20/10</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $left = $example['rotations']['14/7'];
            $right = $example['rotations']['20/10'];
            $rows = [
              'HELP_ROTATION_PAY_ONSITE_DAYS' => ['onsite_days', false],
              'HELP_ROTATION_PAY_HITCHES' => ['hitches', false],
              'HELP_ROTATION_PAY_REGULAR_HOURS' => ['regular_hours', false],
              'HELP_ROTATION_PAY_OVERTIME_HOURS' => ['overtime_hours', false],
              'HELP_ROTATION_PAY_TRAVEL_HOURS' => ['travel_hours', false],
              'HELP_ROTATION_PAY_REGULAR_PAY' => ['regular_pay_cents', true],
              'HELP_ROTATION_PAY_OVERTIME_PAY' => ['overtime_pay_cents', true],
              'HELP_ROTATION_PAY_TRAVEL_PAY' => ['travel_pay_cents', true],
              'HELP_ROTATION_PAY_LOA' => ['loa_cents', true],
              'HELP_ROTATION_PAY_GROSS' => ['gross_cents', true],
              'HELP_ROTATION_PAY_TAX' => ['estimated_tax_cents', true],
              'HELP_ROTATION_PAY_NET' => ['estimated_net_cents', true],
              'HELP_ROTATION_PAY_VACATION' => ['vacation_accrual_cents', true],
            ];
            foreach ($rows as $labelKey => [$field, $isMoney]) {
              $leftValue = $isMoney ? $money((int) $left[$field]) : (string) $left[$field];
              $rightValue = $isMoney ? $money((int) $right[$field]) : (string) $right[$field];
              echo '<tr><th scope="row">' . $h(help_rotation_i18n($labelKey)) . '</th><td>' . $h($leftValue) . '</td><td>' . $h($rightValue) . '</td></tr>';
            }
          ?>
        </tbody>
      </table>
      <p><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_VACATION_NOTE')); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_HOW_TITLE')); ?></h2>
      <p><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_HOW_TEXT')); ?></p>
      <p><a href="/tools/pay-right/alberta/"><?php echo $h(help_rotation_i18n('HELP_ROTATION_PAY_ALBERTA_LINK')); ?></a></p>
    </section>

    <section class="doc-section">
      <p><a class="doc-read-more" href="/auth/signup/?tier=free"><?php echo $h(help_rotation_i18n('TOOLS_SIGNUP_CTA')); ?></a></p>
    </section>
  </div>
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
