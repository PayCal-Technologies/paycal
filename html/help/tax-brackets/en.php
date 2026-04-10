<?php
/**
 * Help: Canadian Tax Brackets — English
 *
 * PURPOSE: Standalone article covering federal and provincial tax bracket
 *          reference data used for payroll estimates. Part of the Help article
 *          system using doc-article / doc-breadcrumb layout.
 * LOCATION: html/help/tax-brackets/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_tb_i18n') === false) {
  function help_tb_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_tb_i18n('HELP_TAX_BRACKETS_TITLE') . ' - [' . help_tb_i18n('SITE_NAME') . ']';
$pageLabel = help_tb_i18n('HELP_TAX_BRACKETS_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_tb_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_tb_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_tb_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_HOW_IT_WORKS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_HOW_IT_WORKS_TEXT_1'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_HOW_IT_WORKS_TEXT_2'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_FEDERAL_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <table class="tax-table doc-table">
        <caption class="visually_hidden"><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_FEDERAL_CAPTION'), ENT_QUOTES, 'UTF-8'); ?></caption>
        <thead>
          <tr>
            <th scope="col"><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_INCOME_RANGE'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th scope="col"><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_TAX_RATE'), ENT_QUOTES, 'UTF-8'); ?></th>
          </tr>
        </thead>
        <tbody>
          <tr><td>$0 – $54,713</td><td>15%</td></tr>
          <tr><td>$54,713 – $109,424</td><td>20.5%</td></tr>
          <tr><td>$109,424 – $173,205</td><td>26%</td></tr>
          <tr><td>$173,205 – $246,752</td><td>29.32%</td></tr>
          <tr><td><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_FEDERAL_OVER_246752'), ENT_QUOTES, 'UTF-8'); ?></td><td>33%</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_PROVINCIAL_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_PROVINCIAL_INTRO'), ENT_QUOTES, 'UTF-8'); ?></p>
      <div class="cards">
        <?php
        $provinces = [
            'Alberta' => [
                ['$0 to $148,269', '10%'],
                ['$148,269 to $177,922', '12%'],
                ['$177,922 to $237,230', '13%'],
                ['$237,230 to $355,845', '14%'],
                ['Over $355,845', '15%'],
            ],
            'British Columbia' => [
                ['$0 to $56,237', '5.06%'],
                ['$56,237 to $112,473', '7.0%'],
                ['$112,473 to $165,267', '10.5%'],
                ['$165,267 to $235,675', '12.29%'],
                ['$235,675 to $300,435', '14.7%'],
                ['Over $300,435', '16.8%'],
            ],
            'Manitoba' => [
                ['$0 to $36,923', '10.8%'],
                ['$36,923 to $79,625', '12.75%'],
                ['$79,625 to $142,741', '17.4%'],
                ['Over $142,741', '17.4%'],
            ],
            'New Brunswick' => [
                ['$0 to $49,746', '9.48%'],
                ['$49,746 to $99,492', '13.97%'],
                ['$99,492 to $176,756', '16.68%'],
                ['$176,756 to $207,239', '20.53%'],
                ['Over $207,239', '20.53%'],
            ],
            'Newfoundland and Labrador' => [
                ['$0 to $41,457', '8.7%'],
                ['$41,457 to $82,913', '14.5%'],
                ['$82,913 to $148,027', '15.8%'],
                ['$148,027 to $207,239', '17.8%'],
                ['$207,239 to $264,750', '19.8%'],
                ['$264,750 to $529,500', '20.8%'],
                ['$529,500 to $1,059,000', '21.3%'],
                ['Over $1,059,000', '21.8%'],
            ],
            'Northwest Territories' => [
                ['$0 to $16,304', '5.9%'],
                ['$16,304 to $32,608', '8.6%'],
                ['$32,608 to $50,836', '12.2%'],
                ['Over $50,836', '14.05%'],
            ],
            'Nova Scotia' => [
                ['$0 to $29,590', '8.79%'],
                ['$29,590 to $59,180', '14.95%'],
                ['$59,180 to $93,000', '16.67%'],
                ['$93,000 to $150,000', '17.5%'],
                ['Over $150,000', '21%'],
            ],
            'Nunavut' => [
                ['$0 to $53,368', '4%'],
                ['$53,368 to $106,737', '7%'],
                ['$106,737 to $173,205', '9%'],
                ['Over $173,205', '11.5%'],
            ],
            'Ontario' => [
                ['$0 to $49,231', '5.05%'],
                ['$49,231 to $98,463', '9.15%'],
                ['$98,463 to $151,237', '11.16%'],
                ['$151,237 to $207,137', '12.16%'],
                ['$207,137 to $246,752', '13.16%'],
                ['Over $246,752', '13.16%'],
            ],
            'Prince Edward Island' => [
                ['$0 to $31,998', '9.48%'],
                ['$31,998 to $67,990', '13.83%'],
                ['$67,990 to $67,991', '16.63%'],
                ['Over $67,991', '16.7%'],
            ],
            'Quebec' => [
                ['$0 to $57,375', '15%'],
                ['$57,375 to $114,750', '20%'],
                ['$114,750 to $172,125', '24%'],
                ['$172,125 to $229,500', '25.75%'],
                ['Over $229,500', '26.75%'],
            ],
            'Saskatchewan' => [
                ['$0 to $52,057', '10.5%'],
                ['$52,057 to $148,734', '12.5%'],
                ['$148,734 to $202,829', '14.5%'],
                ['Over $202,829', '16.5%'],
            ],
            'Yukon' => [
                ['$0 to $55,867', '6.4%'],
                ['$55,867 to $111,733', '9%'],
                ['$111,733 to $173,205', '10.9%'],
                ['$173,205 to $246,752', '12.8%'],
                ['Over $246,752', '15%'],
            ],
        ];

        foreach ($provinces as $province => $brackets) {
          echo "<div class='card'>" . PHP_EOL;
          echo "  <div class='card-header'><h3>" . htmlspecialchars($province, ENT_QUOTES, 'UTF-8') . "</h3></div>" . PHP_EOL;
          echo "  <div class='card-body'>" . PHP_EOL;
          foreach ($brackets as $bracket) {
            echo "    <div class='metric'>" . PHP_EOL;
            echo "      <span>" . htmlspecialchars($bracket[1], ENT_QUOTES, 'UTF-8') . "</span>" . PHP_EOL;
            echo "      <strong>" . htmlspecialchars($bracket[0], ENT_QUOTES, 'UTF-8') . "</strong>" . PHP_EOL;
            echo "    </div>" . PHP_EOL;
          }
          echo "  </div>" . PHP_EOL;
          echo "</div>" . PHP_EOL;
        }
        ?>
      </div>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_EXAMPLE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p>
        <strong><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_EXAMPLE_SUBJECT'), ENT_QUOTES, 'UTF-8'); ?></strong><br>
        <?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_EXAMPLE_FEDERAL'), ENT_QUOTES, 'UTF-8'); ?><br>
        <?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_EXAMPLE_PROVINCIAL'), ENT_QUOTES, 'UTF-8'); ?><br>
        <strong><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_EXAMPLE_NET'), ENT_QUOTES, 'UTF-8'); ?></strong><br>
        <?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_EXAMPLE_NOTE'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_DISCLAIMERS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_DISCLAIMER_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p>
        <?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_OFFICIAL_INFO_PREFIX'), ENT_QUOTES, 'UTF-8'); ?>
        <a href="https://www.canada.ca/en/revenue-agency/services/tax/individuals/factsheets/individuals-fs-2016-1.html" target="_blank" rel="noopener noreferrer">
          <?php echo htmlspecialchars(help_tb_i18n('HELP_TAX_BRACKETS_OFFICIAL_INFO_LINK'), ENT_QUOTES, 'UTF-8'); ?>
        </a>
      </p>
    </section>

  </div><!-- .doc-article-body -->
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
?>
