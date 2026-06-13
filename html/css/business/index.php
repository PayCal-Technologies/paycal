<?php declare(strict_types=1);

?>

.business_context_header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem 1rem;
  margin: 0 0 0.85rem;
  padding: 0;
  border-bottom: 2px solid var(--border);
}

.business_context_name {
  margin: 0;
  flex-shrink: 0;
  font-size: clamp(1rem, 1.6vw, 1.15rem);
  font-weight: 700;
  line-height: 1.2;
}

.business_context_separator {
  flex-shrink: 0;
  align-self: stretch;
  width: 1px;
  min-height: 1.35rem;
  background: var(--border);
}

.business_subnav {
  flex: 1 1 auto;
  min-width: 0;
}

.business_subnav_tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
}

.business_subnav_tab {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.65rem;
  text-decoration: none;
  color: inherit;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  font-size: 0.9rem;
  line-height: 1.2;
}

.business_subnav_tab--active,
.business_subnav_tab[aria-current='page'] {
  border-bottom-color: var(--color-accent, currentColor);
  font-weight: 600;
}

.business_workspace {
  margin-top: 0.5rem;
}

.business_public_preview_lead {
  margin: 0 0 1rem;
  max-width: 48rem;
  line-height: 1.5;
}

.public_extension_disclaimer {
  margin: 1.5rem 0 0;
  padding-top: 0.75rem;
  border-top: 1px solid var(--border);
  font-size: 0.85rem;
  color: var(--text-muted, inherit);
  opacity: 0.85;
}
