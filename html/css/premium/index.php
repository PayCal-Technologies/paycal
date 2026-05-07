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

.premium_hero h1 {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  margin-bottom: 1rem;
  color: var(--color-primary, #29a8e0);
  line-height: 1.2;
}

.premium_deck {
  font-size: 1.05rem;
  color: var(--text-muted, #aaa);
  margin: 0 auto 2rem;
  max-width: 560px;
  line-height: 1.6;
}

.premium_cta_group {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.premium_price_note {
  font-size: 0.88rem;
  color: var(--text-muted, #aaa);
  margin: 0;
}

/* ── Section headings ── */
.premium_compare h2,
.premium_orgs h2,
.premium_security h2,
.premium_access h2,
.premium_faq h2,
.premium_pricing h2 {
  font-size: 1.35rem;
  margin-bottom: 1.25rem;
  color: var(--color-primary, #29a8e0);
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

/* ── Prose sections ── */
.premium_orgs,
.premium_security,
.premium_access {
  max-width: 680px;
}

.premium_orgs p,
.premium_security p,
.premium_access p {
  color: var(--text-muted, #aaa);
  line-height: 1.75;
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

/* ── Pricing ── */
.premium_pricing {
  text-align: center;
  padding: 2rem 1rem 2rem;
  border-top: 1px solid var(--border, rgba(255,255,255,0.08));
}

.premium_price_big {
  font-size: clamp(2.5rem, 6vw, 3.5rem);
  font-weight: 900;
  color: var(--color-primary, #29a8e0);
  margin: 0.5rem 0 0.25rem;
  line-height: 1;
}

.premium_price_big span {
  font-size: 1.1rem;
  font-weight: 400;
  color: var(--text-muted, #aaa);
  vertical-align: middle;
}

.premium_pricing_note {
  font-size: 0.9rem;
  color: var(--text-muted, #aaa);
  margin: 0 0 1.5rem;
}

.premium_stripe_note {
  margin-top: 1rem;
  font-size: 0.82rem;
  color: var(--text-muted, #aaa);
}

.premium_stripe_note a {
  color: var(--color-primary, #29a8e0);
}

/* ── Responsive ── */
@media (max-width: 600px) {
  .premium_compare_table th:nth-child(2),
  .premium_compare_table td:nth-child(2) {
    display: none;
  }

  .premium_page {
    gap: 3rem;
  }
}
