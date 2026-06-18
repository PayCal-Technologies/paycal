<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

.pricing_page {
  width: min(1120px, calc(100vw - 2rem));
  margin: 0 auto;
  padding: 3rem 0 4rem;
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

.pricing_hero {
  max-width: 780px;
}

.pricing_eyebrow {
  margin: 0 0 0.75rem;
  color: var(--color-primary, #29a8e0);
  font-size: 0.82rem;
  font-weight: 700;
  text-transform: uppercase;
}

.pricing_hero h1 {
  margin: 0 0 1rem;
  font-size: clamp(2rem, 5vw, 3.4rem);
  line-height: 1.08;
}

.pricing_deck {
  max-width: 680px;
  margin: 0;
  color: var(--text-muted, #aaa);
  font-size: 1.05rem;
  line-height: 1.7;
}

.pricing_cards {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.pricing_card {
  min-height: 20rem;
  padding: 1.25rem;
  border: 1px solid var(--border, rgba(255,255,255,0.1));
  border-radius: 8px;
  background: var(--surface, rgba(255,255,255,0.04));
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1.25rem;
}

.pricing_card_featured {
  border-color: var(--color-primary, #29a8e0);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary, #29a8e0) 40%, transparent);
}

.pricing_card h2 {
  margin: 0 0 0.7rem;
  font-size: 1.25rem;
}

.pricing_card_summary {
  margin: 0;
  color: var(--text-muted, #aaa);
  line-height: 1.6;
}

.pricing_card .status_text {
  min-height: 1.25rem;
}

.pricing_plan_link {
  align-self: flex-start;
}

.pricing_price {
  margin: auto 0 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.pricing_price span {
  font-size: 2.2rem;
  font-weight: 800;
  line-height: 1;
}

.pricing_price small {
  color: var(--text-muted, #aaa);
}

.pricing_guest_note {
  margin: 0;
  min-height: 2.5rem;
  color: var(--text-muted, #aaa);
  font-size: 0.9rem;
  line-height: 1.45;
}

.pricing_billing_runtime {
  display: none;
}

.pricing_section_header {
  max-width: 760px;
  margin-bottom: 1rem;
}

.pricing_section_header h2,
.pricing_note h2 {
  margin: 0 0 0.5rem;
  color: var(--color-primary, #29a8e0);
  font-size: 1.35rem;
}

.pricing_section_header p {
  margin: 0;
  color: var(--text-muted, #aaa);
  line-height: 1.6;
}

.pricing_matrix_wrap {
  overflow-x: auto;
  border: 1px solid var(--border, rgba(255,255,255,0.1));
  border-radius: 8px;
}

.pricing_matrix {
  width: 100%;
  min-width: 760px;
  border-collapse: collapse;
  font-size: 0.92rem;
}

.pricing_matrix th,
.pricing_matrix td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--border, rgba(255,255,255,0.08));
  vertical-align: top;
}

.pricing_matrix thead th {
  color: var(--color-primary, #29a8e0);
  background: var(--surface, rgba(255,255,255,0.04));
  text-align: left;
}

.pricing_matrix thead th:not(:first-child),
.pricing_matrix td {
  text-align: center;
}

.pricing_matrix tbody th {
  width: 34%;
  text-align: left;
  font-weight: 700;
}

.pricing_matrix tbody th span,
.pricing_matrix tbody th small {
  display: block;
}

.pricing_matrix tbody th small {
  margin-top: 0.3rem;
  color: var(--text-muted, #aaa);
  font-weight: 400;
  line-height: 1.45;
}

.pricing_matrix tbody tr:last-child th,
.pricing_matrix tbody tr:last-child td {
  border-bottom: 0;
}

.pricing_note_grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.pricing_note_grid p {
  margin: 0;
  padding: 1rem;
  border-left: 3px solid var(--color-primary, #29a8e0);
  background: var(--surface, rgba(255,255,255,0.04));
  line-height: 1.6;
}

@media (max-width: 760px) {
  .pricing_cards,
  .pricing_note_grid {
    grid-template-columns: 1fr;
  }

  .pricing_page {
    width: min(100% - 1rem, 1120px);
    padding-top: 2rem;
  }
}
