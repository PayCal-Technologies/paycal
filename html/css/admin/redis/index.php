<?php declare(strict_types=1);

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
.redis-admin {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.redis-admin > :first-child {
  margin-top: 0;
  padding-top: 0;
}

.redis-admin__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.redis-admin__header h1 {
  margin: 0 0 0.35rem 0;
}

.redis-admin__header p {
  margin: 0;
  opacity: 0.9;
}

.redis-admin__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.redis-admin__controls {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.7rem;
}

.redis-admin__controls input[type="text"] {
  width: min(100%, 520px);
}

.redis-admin__control-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.redis-admin__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.redis-admin__grid article h3 {
  margin-top: 0;
}

.redis-admin dl {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.45rem 1rem;
  margin: 0;
}

.redis-admin dt {
  opacity: 0.85;
}

.redis-admin dd {
  margin: 0;
  font-weight: 700;
}

.redis-admin__table {
  width: 100%;
  overflow-x: auto;
}

.redis-admin__table table {
  width: 100%;
  border-collapse: collapse;
}

.redis-admin__table th,
.redis-admin__table td {
  text-align: left;
  padding: 0.5rem 0.55rem;
  border-bottom: 1px solid color-mix(in srgb, var(--panel-text) 22%, transparent);
  white-space: nowrap;
}

.redis-admin__table th {
  font-weight: 700;
}

.status_message {
  margin: 0;
  font-weight: 600;
}

.status_message.is-error {
  color: #c02828;
}

.status_message.is-success {
  color: #1f7a38;
}

.redis-pill {
  display: inline-block;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}

.redis-pill.is-open {
  background: #8a1d1d;
  color: #fff;
}

.redis-pill.is-half-open {
  background: #9a6a00;
  color: #fff;
}

.redis-pill.is-closed {
  background: #1f7a38;
  color: #fff;
}

.redis-pill.is-frozen {
  background: #8a1d1d;
  color: #fff;
}

.redis-pill.is-live {
  background: #1f7a38;
  color: #fff;
}

.redis-pill.is-severity-critical {
  background: #8a1d1d;
  color: #fff;
}

.redis-pill.is-severity-elevated {
  background: #9a6a00;
  color: #fff;
}

.redis-pill.is-severity-warning {
  background: #1f4f99;
  color: #fff;
}

@media (max-width: 1100px) {
  .redis-admin__grid {
    grid-template-columns: 1fr;
  }

  .redis-admin__header {
    flex-direction: column;
  }
}
