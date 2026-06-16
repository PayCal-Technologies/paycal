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

.businesses_datagrid_skeleton_row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
  padding: 0.45rem 0.5rem;
  border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.18));
}

.businesses_datagrid_skeleton_row--3 {
  grid-template-columns: repeat(3, 1fr);
}

.businesses_datagrid_skeleton_row--5 {
  grid-template-columns: repeat(5, 1fr);
}

.businesses_datagrid_skeleton_cell {
  height: 0.8em;
  min-height: 0.8rem;
  overflow: hidden;
  background: linear-gradient(
    90deg,
    var(--skeleton-base, color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.2)) 60%, var(--surface, #1e2633))),
    var(--skeleton-shine, color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.2)) 20%, var(--surface, #1e2633))),
    var(--skeleton-base, color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.2)) 60%, var(--surface, #1e2633)))
  );
  background-size: 800px 100%;
  animation: sk-shimmer 1.6s infinite linear;
}

@media (prefers-reduced-motion: reduce) {
  .businesses_datagrid_skeleton_cell {
    animation: none;
    background: var(--skeleton-base, color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.2)) 55%, var(--surface, #1e2633)));
  }
}

.public_extension_disclaimer {
  margin: 1.5rem 0 0;
  padding-top: 0.75rem;
  border-top: 1px solid var(--border);
  font-size: 0.85rem;
  color: var(--text-muted, inherit);
  opacity: 0.85;
}
