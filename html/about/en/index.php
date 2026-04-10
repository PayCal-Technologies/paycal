<?php declare(strict_types=1);

/**
 * About page - English.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   About
 *
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

$i18n = [];
$i18nKeys = [
  'ABOUT_EN_SECTION_ABOUT_PAYCAL',
  'ABOUT_EN_SECTION_WHAT_PAYCAL_IS',
  'ABOUT_EN_SECTION_FREE_PREMIUM',
  'ABOUT_EN_TABLE_ARIA_FREE_PREMIUM',
  'ABOUT_EN_TABLE_PLAN',
  'ABOUT_EN_TABLE_WHAT_YOU_GET',
  'ABOUT_EN_SECTION_WHAT_IT_HELPS_WITH',
  'ABOUT_EN_SECTION_KEY_FEATURES',
  'ABOUT_EN_SECTION_WHO_ITS_FOR',
  'ABOUT_EN_SECTION_SECURITY_PRIVACY',
  'ABOUT_EN_SECTION_ACCESSIBILITY',
  'ABOUT_EN_SECTION_TRANSPARENCY',
  'ABOUT_EN_SECTION_BILLING_SUPPORT',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}
?>
<article class="article doc-article">
  <section class="doc-article-body">
    <section class="doc-section">
      <h2><?php echo $i18n['ABOUT_EN_SECTION_ABOUT_PAYCAL']; ?></h2>
      <p>PayCal helps people track their work, understand their pay, and keep clear payroll records.</p>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_WHAT_PAYCAL_IS']; ?></h2>
      <p>PayCal is a payroll tracking tool that shows your earnings, estimates deductions, and keeps your work data organized.</p>
      <p>It is free for personal use and designed to make pay periods easier to understand before payday.</p>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_FREE_PREMIUM']; ?></h2>
      <p>PayCal works fully for individuals at no cost.</p>
      <p>Organizations can choose to upgrade to a Premium plan for shared features.</p>

      <table class="table" aria-label="<?php echo $i18n['ABOUT_EN_TABLE_ARIA_FREE_PREMIUM']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['ABOUT_EN_TABLE_PLAN']; ?></th>
            <th scope="col"><?php echo $i18n['ABOUT_EN_TABLE_WHAT_YOU_GET']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Free</td>
            <td>Personal work tracking, earnings estimates, pay-period visibility, and exports</td>
          </tr>
          <tr>
            <td>Premium (Organizations)</td>
            <td>Shared access, organization management, and advanced workflows</td>
          </tr>
          <tr>
            <td>Pricing</td>
            <td>$4.99 CAD/month per organization</td>
          </tr>
        </tbody>
      </table>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_WHAT_IT_HELPS_WITH']; ?></h2>
      <ul class="doc-fact-list">
        <li>Track regular hours, overtime, travel, and allowances</li>
        <li>See estimated earnings and deductions before payday</li>
        <li>Follow pay periods using a calendar view</li>
        <li>Manage work across multiple job sites</li>
      </ul>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_KEY_FEATURES']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong>Calendar-based entry:</strong> Log and review work day by day</li>
        <li><strong>Multi-site tracking:</strong> Keep records organized across locations</li>
        <li><strong>Pay-period previews:</strong> See how entries affect your current pay cycle</li>
        <li><strong>Consistent calculations:</strong> Reliable estimates for deductions and net pay</li>
        <li><strong>Export options:</strong> Download records as PDF, CSV, or TXT</li>
        <li><strong>Easy navigation:</strong> Works with keyboard, touch, or mouse</li>
        <li><strong>Clear breakdowns:</strong> Simple view of earnings and deductions</li>
      </ul>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_WHO_ITS_FOR']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong>Individuals:</strong> Track your work and pay history for free</li>
        <li><strong>Organizations:</strong> Use Premium for shared access and coordination</li>
      </ul>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_SECURITY_PRIVACY']; ?></h2>
      <p>PayCal is designed to keep your data private and secure.</p>
      <ul class="doc-fact-list">
        <li>Sensitive data is encrypted</li>
        <li>Data is stored securely and handled with minimal exposure</li>
        <li>Sessions clear sensitive information when context changes</li>
        <li>Personal identifiers are limited wherever possible</li>
      </ul>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_ACCESSIBILITY']; ?></h2>
      <p>Accessibility is built in by default.</p>
      <ul class="doc-fact-list">
        <li>Works with screen readers and keyboard navigation</li>
        <li>Readable typography options, including dyslexia-friendly styles</li>
        <li>Strong contrast and visual clarity</li>
      </ul>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_TRANSPARENCY']; ?></h2>
      <p>Privacy, security, and accessibility are part of how PayCal is built.</p>
      <ul class="doc-fact-list">
        <li>Security measures are built into the system</li>
        <li>Accessibility work is openly documented</li>
        <li>Key system behavior is explained in public pages</li>
      </ul>

      <h2><?php echo $i18n['ABOUT_EN_SECTION_BILLING_SUPPORT']; ?></h2>
      <p>Premium subscriptions are processed by Stripe.</p>
      <p>For billing support, visit <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">Stripe support</a> or <a href="/contact/">contact us</a> for general questions.</p>
    </section>
  </section>
</article>

