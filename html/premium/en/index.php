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
  'PREMIUM_PAGE_FREE_TAGLINE',
  'PREMIUM_PAGE_PREMIUM_TAGLINE',
  'PREMIUM_PAGE_CTA_UPGRADE',
  'PREMIUM_PAGE_CTA_COMPARE',
  'PREMIUM_PAGE_WHO_TITLE',
  'PREMIUM_PAGE_WHO_BIZ_TITLE',
  'PREMIUM_PAGE_WHO_BIZ_BODY',
  'PREMIUM_PAGE_WHO_CONTRACTOR_TITLE',
  'PREMIUM_PAGE_WHO_CONTRACTOR_BODY',
  'PREMIUM_PAGE_WHO_BOOKKEEPER_TITLE',
  'PREMIUM_PAGE_WHO_BOOKKEEPER_BODY',
  'PREMIUM_PAGE_WHO_TEAMS_TITLE',
  'PREMIUM_PAGE_WHO_TEAMS_BODY',
  'PREMIUM_PAGE_COMPARE_TITLE',
  'PREMIUM_PAGE_COL_FEATURE',
  'PREMIUM_PAGE_COL_FREE',
  'PREMIUM_PAGE_COL_PREMIUM',
  'PREMIUM_PAGE_ORGS_TITLE',
  'PREMIUM_PAGE_ORG_FEATURE1_TITLE',
  'PREMIUM_PAGE_ORG_FEATURE1_BODY',
  'PREMIUM_PAGE_ORG_FEATURE2_TITLE',
  'PREMIUM_PAGE_ORG_FEATURE2_BODY',
  'PREMIUM_PAGE_ORG_FEATURE3_TITLE',
  'PREMIUM_PAGE_ORG_FEATURE3_BODY',
  'PREMIUM_PAGE_ORG_FEATURE4_TITLE',
  'PREMIUM_PAGE_ORG_FEATURE4_BODY',
  'PREMIUM_PAGE_TRUST_TITLE',
  'PREMIUM_PAGE_TRUST_SECURE_TITLE',
  'PREMIUM_PAGE_TRUST_SECURE_BODY',
  'PREMIUM_PAGE_TRUST_PRIVATE_TITLE',
  'PREMIUM_PAGE_TRUST_PRIVATE_BODY',
  'PREMIUM_PAGE_TRUST_ACCESS_TITLE',
  'PREMIUM_PAGE_TRUST_ACCESS_BODY',
  'PREMIUM_PAGE_TRUST_TRANSPARENT_TITLE',
  'PREMIUM_PAGE_TRUST_TRANSPARENT_BODY',
  'PREMIUM_PAGE_FAQ_TITLE',
  'PREMIUM_PAGE_FAQ_Q1',
  'PREMIUM_PAGE_FAQ_A1',
  'PREMIUM_PAGE_FAQ_Q2',
  'PREMIUM_PAGE_FAQ_A2',
  'PREMIUM_PAGE_FAQ_Q3',
  'PREMIUM_PAGE_FAQ_A3',
  'PREMIUM_PAGE_FAQ_Q4',
  'PREMIUM_PAGE_FAQ_A4',
  'PREMIUM_PAGE_FAQ_Q5',
  'PREMIUM_PAGE_FAQ_A5',
  'PREMIUM_PAGE_FINAL_TITLE',
  'PREMIUM_PAGE_FINAL_BODY',
  'PREMIUM_PAGE_PRICING_NOTE',
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

  <!-- ── 1. HERO ── -->
  <section class="premium_hero" aria-labelledby="premium-hero-heading">
    <p class="premium_eyebrow">Premium</p>
    <h1 id="premium-hero-heading"><?php echo $i18n['PREMIUM_PAGE_HERO_H1']; ?></h1>
    <p class="premium_hero_deck"><?php echo $i18n['PREMIUM_PAGE_HERO_DECK']; ?></p>
    <p class="premium_price_note"><?php echo $i18n['PREMIUM_PAGE_PRICE_NOTE']; ?></p>
    <div class="premium_cta_group">
      <a href="<?php echo $ctaHrefSafe; ?>" class="btn btn_primary"><?php echo $i18n['PREMIUM_PAGE_CTA_UPGRADE']; ?></a>
      <a href="#premium-compare" class="btn btn_secondary"><?php echo $i18n['PREMIUM_PAGE_CTA_COMPARE']; ?></a>
    </div>
  </section>

  <!-- ── 2. WHO PREMIUM IS FOR ── -->
  <section class="premium_who" aria-labelledby="premium-who-heading">
    <h2 id="premium-who-heading"><?php echo $i18n['PREMIUM_PAGE_WHO_TITLE']; ?></h2>
    <div class="premium_who_cards">
      <div class="premium_who_card">
        <h3><?php echo $i18n['PREMIUM_PAGE_WHO_BIZ_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_WHO_BIZ_BODY']; ?></p>
      </div>
      <div class="premium_who_card">
        <h3><?php echo $i18n['PREMIUM_PAGE_WHO_CONTRACTOR_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_WHO_CONTRACTOR_BODY']; ?></p>
      </div>
      <div class="premium_who_card">
        <h3><?php echo $i18n['PREMIUM_PAGE_WHO_BOOKKEEPER_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_WHO_BOOKKEEPER_BODY']; ?></p>
      </div>
      <div class="premium_who_card">
        <h3><?php echo $i18n['PREMIUM_PAGE_WHO_TEAMS_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_WHO_TEAMS_BODY']; ?></p>
      </div>
    </div>
  </section>

  <!-- ── 3. FREE VS PREMIUM COMPARISON ── -->
  <section class="premium_compare" aria-labelledby="premium-compare-heading" id="premium-compare">
    <div class="premium_compare_intro">
      <div>
        <h2 id="premium-compare-heading"><?php echo $i18n['PREMIUM_PAGE_COMPARE_TITLE']; ?></h2>
      </div>
      <div class="premium_plan_tag premium_plan_tag_free">
        <strong><?php echo $i18n['PREMIUM_PAGE_COL_FREE']; ?></strong>
        <p><?php echo $i18n['PREMIUM_PAGE_FREE_TAGLINE']; ?></p>
      </div>
      <div class="premium_plan_tag premium_plan_tag_premium">
        <strong><?php echo $i18n['PREMIUM_PAGE_COL_PREMIUM']; ?></strong>
        <p><?php echo $i18n['PREMIUM_PAGE_PREMIUM_TAGLINE']; ?></p>
      </div>
    </div>
    <table class="premium_compare_table" role="table">
      <thead>
        <tr>
          <th scope="col"><?php echo $i18n['PREMIUM_PAGE_COL_FEATURE']; ?></th>
          <th scope="col"><?php echo $i18n['PREMIUM_PAGE_COL_FREE']; ?></th>
          <th scope="col"><?php echo $i18n['PREMIUM_PAGE_COL_PREMIUM']; ?></th>
        </tr>
      </thead>
      <tbody>
        <tr><td>Personal work tracking</td>              <td class="check" aria-label="Included">✓</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Earnings calculations</td>               <td class="check" aria-label="Included">✓</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Calendar views</td>                      <td class="check" aria-label="Included">✓</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Export records</td>                      <td class="check" aria-label="Included">✓</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Create organizations</td>                <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Invite team members</td>                 <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Role management</td>                     <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Shared organization access</td>          <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Team payroll visibility</td>             <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
        <tr><td>Organization tools</td>                  <td class="dash"  aria-label="Not included">—</td><td class="check" aria-label="Included">✓</td></tr>
      </tbody>
    </table>
  </section>

  <!-- ── 4. ORGANIZATION FEATURES ── -->
  <section class="premium_orgs" aria-labelledby="premium-orgs-heading">
    <h2 id="premium-orgs-heading"><?php echo $i18n['PREMIUM_PAGE_ORGS_TITLE']; ?></h2>
    <div class="premium_org_features">
      <div class="premium_org_feature">
        <h3><?php echo $i18n['PREMIUM_PAGE_ORG_FEATURE1_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_ORG_FEATURE1_BODY']; ?></p>
      </div>
      <div class="premium_org_feature">
        <h3><?php echo $i18n['PREMIUM_PAGE_ORG_FEATURE2_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_ORG_FEATURE2_BODY']; ?></p>
      </div>
      <div class="premium_org_feature">
        <h3><?php echo $i18n['PREMIUM_PAGE_ORG_FEATURE3_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_ORG_FEATURE3_BODY']; ?></p>
      </div>
      <div class="premium_org_feature">
        <h3><?php echo $i18n['PREMIUM_PAGE_ORG_FEATURE4_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_ORG_FEATURE4_BODY']; ?></p>
      </div>
    </div>
  </section>

  <!-- ── 5. TRUST / SECURITY ── -->
  <section class="premium_trust" aria-labelledby="premium-trust-heading">
    <h2 id="premium-trust-heading"><?php echo $i18n['PREMIUM_PAGE_TRUST_TITLE']; ?></h2>
    <div class="premium_trust_pillars">
      <div class="premium_trust_pillar">
        <h3><?php echo $i18n['PREMIUM_PAGE_TRUST_SECURE_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_TRUST_SECURE_BODY']; ?></p>
      </div>
      <div class="premium_trust_pillar">
        <h3><?php echo $i18n['PREMIUM_PAGE_TRUST_PRIVATE_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_TRUST_PRIVATE_BODY']; ?></p>
      </div>
      <div class="premium_trust_pillar">
        <h3><?php echo $i18n['PREMIUM_PAGE_TRUST_ACCESS_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_TRUST_ACCESS_BODY']; ?></p>
      </div>
      <div class="premium_trust_pillar">
        <h3><?php echo $i18n['PREMIUM_PAGE_TRUST_TRANSPARENT_TITLE']; ?></h3>
        <p><?php echo $i18n['PREMIUM_PAGE_TRUST_TRANSPARENT_BODY']; ?></p>
      </div>
    </div>
  </section>

  <!-- ── 6. FAQ ── -->
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
      <div class="premium_faq_item">
        <dt><?php echo $i18n['PREMIUM_PAGE_FAQ_Q5']; ?></dt>
        <dd><?php echo $i18n['PREMIUM_PAGE_FAQ_A5']; ?></dd>
      </div>
    </dl>
  </section>

  <!-- ── 7. FINAL CTA ── -->
  <section class="premium_final_cta" aria-labelledby="premium-final-heading">
    <h2 id="premium-final-heading"><?php echo $i18n['PREMIUM_PAGE_FINAL_TITLE']; ?></h2>
    <p><?php echo $i18n['PREMIUM_PAGE_FINAL_BODY']; ?></p>
    <a href="<?php echo $ctaHrefSafe; ?>" class="btn btn_primary"><?php echo $i18n['PREMIUM_PAGE_CTA_UPGRADE']; ?></a>
    <p class="premium_pricing_note"><?php echo $i18n['PREMIUM_PAGE_PRICING_NOTE']; ?></p>
    <p class="premium_stripe_note">
      <?php echo $i18n['PREMIUM_PAGE_STRIPE_NOTE']; ?>
      <a href="/contact"><?php echo $i18n['PREMIUM_PAGE_STRIPE_LINK']; ?></a>.
    </p>
  </section>

</div>
