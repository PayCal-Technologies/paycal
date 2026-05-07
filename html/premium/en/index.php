<?php declare(strict_types=1);
/**
 * Premium landing page — English template.
 *
 * Variables provided by parent controller (premium/index.php):
 *   $ctaHref         string  URL for upgrade CTA (/profile#panel-billing or /auth)
 *   $isAuthenticated bool    Whether the current visitor is signed in
 */

$i18nKeys = [
  'PREMIUM_PAGE_HERO_H1',
  'PREMIUM_PAGE_HERO_DECK',
  'PREMIUM_PAGE_PRICE_NOTE',
  'PREMIUM_PAGE_COMPARE_TITLE',
  'PREMIUM_PAGE_COL_FEATURE',
  'PREMIUM_PAGE_COL_FREE',
  'PREMIUM_PAGE_COL_PREMIUM',
  'PREMIUM_PAGE_ORGS_TITLE',
  'PREMIUM_PAGE_ORGS_BODY',
  'PREMIUM_PAGE_SECURITY_TITLE',
  'PREMIUM_PAGE_SECURITY_BODY',
  'PREMIUM_PAGE_ACCESS_TITLE',
  'PREMIUM_PAGE_ACCESS_BODY',
  'PREMIUM_PAGE_FAQ_TITLE',
  'PREMIUM_PAGE_FAQ_Q1',
  'PREMIUM_PAGE_FAQ_A1',
  'PREMIUM_PAGE_FAQ_Q2',
  'PREMIUM_PAGE_FAQ_A2',
  'PREMIUM_PAGE_FAQ_Q3',
  'PREMIUM_PAGE_FAQ_A3',
  'PREMIUM_PAGE_FAQ_Q4',
  'PREMIUM_PAGE_FAQ_A4',
  'PREMIUM_PAGE_PRICING_TITLE',
  'PREMIUM_PAGE_PRICING_NOTE',
  'PREMIUM_PAGE_CTA_UPGRADE',
  'PREMIUM_PAGE_STRIPE_NOTE',
  'PREMIUM_PAGE_STRIPE_LINK',
];

$i18n = [];
foreach ($i18nKeys as $k) {
  $i18n[$k] = htmlspecialchars(premium_i18n($k), ENT_QUOTES, 'UTF-8');
}

$ctaHrefSafe = htmlspecialchars($ctaHref ?? '/auth', ENT_QUOTES, 'UTF-8');
?>

<div class="premium_page" id="premium-page">

  <!-- ── HERO ── -->
  <section class="premium_hero" aria-labelledby="premium-hero-heading">
    <h1 id="premium-hero-heading"><?php echo $i18n['PREMIUM_PAGE_HERO_H1']; ?></h1>
    <p class="premium_deck"><?php echo $i18n['PREMIUM_PAGE_HERO_DECK']; ?></p>
    <div class="premium_cta_group">
      <a href="<?php echo $ctaHrefSafe; ?>" class="btn btn_primary"><?php echo $i18n['PREMIUM_PAGE_CTA_UPGRADE']; ?></a>
      <p class="premium_price_note"><?php echo $i18n['PREMIUM_PAGE_PRICE_NOTE']; ?></p>
    </div>
  </section>

  <!-- ── FREE VS PREMIUM COMPARISON ── -->
  <section class="premium_compare" aria-labelledby="premium-compare-heading">
    <h2 id="premium-compare-heading"><?php echo $i18n['PREMIUM_PAGE_COMPARE_TITLE']; ?></h2>
    <table class="premium_compare_table" role="table">
      <thead>
        <tr>
          <th scope="col"><?php echo $i18n['PREMIUM_PAGE_COL_FEATURE']; ?></th>
          <th scope="col"><?php echo $i18n['PREMIUM_PAGE_COL_FREE']; ?></th>
          <th scope="col"><?php echo $i18n['PREMIUM_PAGE_COL_PREMIUM']; ?></th>
        </tr>
      </thead>
      <tbody>
        <tr><td>Track work hours and earnings</td>               <td class="check" aria-label="Included">✓</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Pay period reports and history</td>              <td class="check" aria-label="Included">✓</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Join organizations as a member</td>              <td class="check" aria-label="Included">✓</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Create and manage your own teams</td>            <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Invite managers and employees</td>               <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Role-based permissions</td>                      <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Import and export payroll records</td>           <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Advanced earnings analytics</td>                 <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Audit history for team actions</td>              <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Up to 1,000 members per organization</td>        <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Priority future features</td>                    <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
      </tbody>
    </table>
  </section>

  <!-- ── WHAT ARE ORGANIZATIONS? ── -->
  <section class="premium_orgs" aria-labelledby="premium-orgs-heading">
    <h2 id="premium-orgs-heading"><?php echo $i18n['PREMIUM_PAGE_ORGS_TITLE']; ?></h2>
    <p><?php echo $i18n['PREMIUM_PAGE_ORGS_BODY']; ?></p>
  </section>

  <!-- ── SECURITY ── -->
  <section class="premium_security" aria-labelledby="premium-security-heading">
    <h2 id="premium-security-heading"><?php echo $i18n['PREMIUM_PAGE_SECURITY_TITLE']; ?></h2>
    <p><?php echo $i18n['PREMIUM_PAGE_SECURITY_BODY']; ?></p>
  </section>

  <!-- ── ACCESSIBILITY ── -->
  <section class="premium_access" aria-labelledby="premium-access-heading">
    <h2 id="premium-access-heading"><?php echo $i18n['PREMIUM_PAGE_ACCESS_TITLE']; ?></h2>
    <p><?php echo $i18n['PREMIUM_PAGE_ACCESS_BODY']; ?></p>
  </section>

  <!-- ── FAQ ── -->
  <section class="premium_faq" aria-labelledby="premium-faq-heading">
    <h2 id="premium-faq-heading"><?php echo $i18n['PREMIUM_PAGE_FAQ_TITLE']; ?></h2>
    <dl class="premium_faq_list">
      <div class="premium_faq_item">
        <dt><?php echo $i18n['PREMIUM_PAGE_FAQ_Q1']; ?></dt>
        <dd><?php echo $i18n['PREMIUM_PAGE_FAQ_A1']; ?></dd>
      </div>
      <div class="premium_faq_item">
        <dt><?php echo $i18n['PREMIUM_PAGE_FAQ_Q2']; ?></dt>
        <dd><?php echo $i18n['PREMIUM_PAGE_FAQ_A2']; ?></dd>
      </div>
      <div class="premium_faq_item">
        <dt><?php echo $i18n['PREMIUM_PAGE_FAQ_Q3']; ?></dt>
        <dd><?php echo $i18n['PREMIUM_PAGE_FAQ_A3']; ?></dd>
      </div>
      <div class="premium_faq_item">
        <dt><?php echo $i18n['PREMIUM_PAGE_FAQ_Q4']; ?></dt>
        <dd><?php echo $i18n['PREMIUM_PAGE_FAQ_A4']; ?></dd>
      </div>
    </dl>
  </section>

  <!-- ── PRICING + CTA ── -->
  <section class="premium_pricing" aria-labelledby="premium-pricing-heading">
    <h2 id="premium-pricing-heading"><?php echo $i18n['PREMIUM_PAGE_PRICING_TITLE']; ?></h2>
    <p class="premium_price_big">$4.99 <span>CAD/month</span></p>
    <p class="premium_pricing_note"><?php echo $i18n['PREMIUM_PAGE_PRICING_NOTE']; ?></p>
    <a href="<?php echo $ctaHrefSafe; ?>" class="btn btn_primary"><?php echo $i18n['PREMIUM_PAGE_CTA_UPGRADE']; ?></a>
    <p class="premium_stripe_note">
      <?php echo $i18n['PREMIUM_PAGE_STRIPE_NOTE']; ?>
      <a href="/contact"><?php echo $i18n['PREMIUM_PAGE_STRIPE_LINK']; ?></a>.
    </p>
  </section>

</div>
