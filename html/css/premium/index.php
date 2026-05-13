<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

/* Premium landing page styles */

.premium_page {
  max-width: min(80vw, 900px);
  margin: 2rem auto;
  padding: 0 1rem 4rem;
  display: flex;
  flex-direction: column;
  gap: 4rem;
}

/* ── Hero ── */
.premium_hero {
  text-align: center;
  padding: 3rem 1rem 1.5rem;
}

.premium_eyebrow {
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-primary, #29a8e0);
  margin: 0 0 1rem;
}

.premium_hero h1 {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  margin-bottom: 1rem;
  color: var(--text, #e0e0e0);
  line-height: 1.2;
}

.premium_hero_deck {
  font-size: 1.05rem;
  color: var(--text-muted, #aaa);
  margin: 0 auto 1.25rem;
  max-width: 600px;
  line-height: 1.7;
}

.premium_price_note {
  font-size: 0.9rem;
  color: var(--text-muted, #aaa);
  margin: 0 0 1.75rem;
}

.premium_cta_group {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.75rem;
}

/* ── Section headings ── */
.premium_who h2,
.premium_compare h2,
.premium_orgs h2,
.premium_trust h2,
.premium_faq h2,
.premium_final_cta h2 {
  font-size: 1.35rem;
  margin-bottom: 1.25rem;
  color: var(--color-primary, #29a8e0);
}

/* ── Who Premium Is For ── */
.premium_who_cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.25rem;
  margin-top: 0.5rem;
}

.premium_who_card {
  background: var(--surface, rgba(255,255,255,0.04));
  border: 1px solid var(--border, rgba(255,255,255,0.08));
  border-radius: 6px;
  padding: 1.25rem 1.5rem;
}

.premium_who_card h3 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.premium_who_card p {
  color: var(--text-muted, #aaa);
  font-size: 0.92rem;
  line-height: 1.65;
  margin: 0;
}

/* ── Compare intro ── */
.premium_compare_intro {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
  align-items: start;
}

.premium_plan_tag {
  border: 1px solid var(--border, rgba(255,255,255,0.08));
  border-radius: 6px;
  padding: 0.85rem 1rem;
}

.premium_plan_tag strong {
  display: block;
  font-size: 0.9rem;
  font-weight: 700;
  margin-bottom: 0.35rem;
}

.premium_plan_tag_free strong {
  color: var(--text-muted, #aaa);
}

.premium_plan_tag_premium strong {
  color: var(--color-primary, #29a8e0);
}

.premium_plan_tag p {
  font-size: 0.82rem;
  color: var(--text-muted, #aaa);
  line-height: 1.5;
  margin: 0;
}

/* ── Comparison table ── */
.premium_compare_table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.92rem;
}

.premium_compare_table th {
  text-align: left;
  padding: 0.6rem 1rem;
  border-bottom: 2px solid var(--color-primary, #29a8e0);
  color: var(--color-primary, #29a8e0);
  font-weight: 700;
}

.premium_compare_table th:nth-child(2),
.premium_compare_table th:nth-child(3) {
  text-align: center;
  width: 5.5rem;
}

.premium_compare_table td {
  padding: 0.55rem 1rem;
  border-bottom: 1px solid var(--border, rgba(255,255,255,0.08));
}

.premium_compare_table td:nth-child(2),
.premium_compare_table td:nth-child(3) {
  text-align: center;
  font-weight: 700;
}

.premium_compare_table .check {
  color: var(--color-primary, #29a8e0);
}

.premium_compare_table .dash {
  color: var(--text-muted, #aaa);
}

.premium_compare_table tbody tr:hover {
  background: var(--hover, rgba(255,255,255,0.03));
}

/* ── Organization features ── */
.premium_org_features {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.25rem;
  margin-top: 0.5rem;
}

.premium_org_feature {
  border-left: 3px solid var(--color-primary, #29a8e0);
  padding: 0.5rem 0 0.5rem 1.25rem;
}

.premium_org_feature h3 {
  font-size: 0.95rem;
  font-weight: 700;
  margin: 0 0 0.45rem;
}

.premium_org_feature p {
  color: var(--text-muted, #aaa);
  font-size: 0.9rem;
  line-height: 1.65;
  margin: 0;
}

/* ── Trust pillars ── */
.premium_trust_pillars {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1.25rem;
  margin-top: 0.5rem;
}

.premium_trust_pillar {
  background: var(--surface, rgba(255,255,255,0.04));
  border: 1px solid var(--border, rgba(255,255,255,0.08));
  border-radius: 6px;
  padding: 1.25rem 1.5rem;
}

.premium_trust_pillar h3 {
  font-size: 0.95rem;
  font-weight: 700;
  margin: 0 0 0.45rem;
  color: var(--color-primary, #29a8e0);
}

.premium_trust_pillar p {
  color: var(--text-muted, #aaa);
  font-size: 0.88rem;
  line-height: 1.65;
  margin: 0;
}

/* ── FAQ ── */
.premium_faq_list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin: 0;
  padding: 0;
}

.premium_faq_item dt {
  font-weight: 700;
  margin-bottom: 0.35rem;
}

.premium_faq_item dd {
  color: var(--text-muted, #aaa);
  line-height: 1.7;
  margin: 0;
}

/* ── Final CTA ── */
.premium_final_cta {
  text-align: center;
  padding: 2.5rem 1rem;
  border-top: 1px solid var(--border, rgba(255,255,255,0.08));
}

.premium_final_cta h2 {
  font-size: clamp(1.2rem, 3vw, 1.7rem);
  line-height: 1.3;
}

.premium_final_cta p:first-of-type {
  color: var(--text-muted, #aaa);
  max-width: 560px;
  margin: 0 auto 1.75rem;
  line-height: 1.7;
}

.premium_pricing_note {
  font-size: 0.9rem;
  color: var(--text-muted, #aaa);
  margin: 1rem 0 0;
}

.premium_stripe_note {
  margin-top: 0.75rem;
  font-size: 0.82rem;
  color: var(--text-muted, #aaa);
}

.premium_stripe_note a {
  color: var(--color-primary, #29a8e0);
}

/* ── Responsive ── */
@media (max-width: 700px) {
  .premium_compare_table th:nth-child(2),
  .premium_compare_table td:nth-child(2) {
    display: none;
  }

  .premium_compare_intro {
    grid-template-columns: 1fr;
  }

  .premium_plan_tag_free {
    display: none;
  }

  .premium_page {
    gap: 3rem;
  }
}
