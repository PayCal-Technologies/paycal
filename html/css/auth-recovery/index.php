<?php declare(strict_types=1);

header('Content-Type: text/css; charset=utf-8');
?>
body {
  min-height: 100vh;
  margin: 0;
  background: linear-gradient(180deg, #0b1018 0%, #141d2b 100%);
  color: #eff5ff;
  font-family: var(--sans-serif);
}
.recovery-shell {
  max-width: 540px;
  margin: 0 auto;
  padding: 2.5rem 1rem 4rem;
}
.recovery-header {
  margin-bottom: 1rem;
}
.recovery-header h1 {
  margin: 1rem 0 0.45rem;
  font-family: var(--sans-serif);
  font-size: clamp(1.65rem, 3vw, 2rem);
  line-height: 1.15;
  font-weight: 800;
  letter-spacing: 0;
}
.recovery-header p,
.recovery-card p,
.recovery-status,
.recovery-card label {
  color: #c7d6ee;
}
.recovery-back {
  color: #9dd4ff;
  font-size: 0.92rem;
  font-weight: 700;
  text-decoration-thickness: 1px;
  text-underline-offset: 0.18rem;
}
.recovery-card {
  --recovery-control-height: 3rem;
  background: rgba(15, 22, 36, 0.64);
  border: 1px solid rgba(150, 176, 211, 0.16);
  border-radius: 10px;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
  padding: 1.35rem;
}
.recovery-status {
  grid-column: 1 / -1;
  margin: 0.2rem 0 0;
  min-height: 1.15rem;
  max-width: none;
  text-align: left;
  font-size: 0.9rem;
}
.recovery-status:empty {
  display: none;
}
.recovery-status[data-tone="sent"] {
  color: #9ee6c0;
  background: transparent;
  border: 0;
  box-shadow: none;
  border-radius: 0;
  font-weight: 700;
  padding: 0;
}
.recovery-status[data-tone="error"] {
  color: #ffd7d7;
  background: transparent;
  border: 0;
  box-shadow: none;
  border-radius: 0;
  font-weight: 700;
  padding: 0;
}
.recovery-status-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2rem;
  margin-left: 0.75rem;
  border: 1px solid rgba(255, 215, 215, 0.72);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.12);
  color: #fff3f3;
  font: inherit;
  font-weight: 800;
  padding: 0.32rem 0.65rem;
  cursor: pointer;
}
.recovery-status-action:disabled {
  cursor: wait;
  opacity: 0.65;
}
.recovery-panel {
  display: grid;
  gap: 32px;
}
.recovery-panel.is-hidden,
.is-hidden {
  display: none;
}
.recovery-card input {
  width: 100%;
  margin: 0;
  min-height: var(--recovery-control-height);
  height: var(--recovery-control-height);
  box-sizing: border-box;
  border-radius: 8px;
  border: 1px solid rgba(122, 157, 201, 0.3);
  background: rgba(11, 18, 30, 0.85);
  color: #eff5ff;
  padding: 0.75rem 0.85rem;
  font: inherit;
}
.recovery-email-form {
  display: grid;
  gap: 0.35rem;
}
.recovery-email-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.7rem;
  align-items: stretch;
}
.recovery-code-form {
  display: grid;
  grid-template-columns: 1fr;
  gap: 28px;
}
.recovery-code-form .recovery-actions {
  margin-top: 8px;
}
.recovery-field {
  min-width: 0;
}
.recovery-field label {
  display: block;
  margin-bottom: 0.28rem;
  font-size: 0.94rem;
  font-weight: 700;
  line-height: 1.25;
}
.recovery-code-form .recovery-field {
  display: grid;
  gap: 0.24rem;
}
#recovery-key {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0;
}
.recovery-card input[aria-invalid="true"] {
  border-color: rgba(235, 126, 126, 0.9);
  box-shadow: 0 0 0 3px rgba(235, 126, 126, 0.18);
}
.recovery-field-error {
  min-height: 0;
  margin: 0;
  color: #ffd7d7;
  font-size: 0.86rem;
}
.recovery-field-error:not(:empty) {
  min-height: 1rem;
}
.recovery-field-error[data-tone="good"] {
  color: #9ee6c0;
}
.recovery-actions {
  grid-column: 1 / -1;
  display: flex;
  justify-content: stretch;
  align-items: center;
  margin-top: 0;
}
.recovery-actions .btn.btn_primary {
  width: 100%;
  min-height: 2.95rem;
  margin-left: 0;
  color: #fff;
}
.recovery-email-form .btn,
.btn.btn_secondary {
  box-sizing: border-box;
  border: 1px solid rgba(122, 157, 201, 0.34);
  background: rgba(14, 21, 34, 0.7);
  color: #dce8fa;
  border-radius: 8px;
  padding: 0 0.9rem;
  cursor: pointer;
  min-height: var(--recovery-control-height);
  height: var(--recovery-control-height);
  line-height: 1;
  white-space: nowrap;
}
.recovery-email-form .btn {
  align-self: stretch;
}
.btn[disabled],
.btn[aria-disabled="true"] {
  cursor: not-allowed;
  opacity: 0.62;
}
.btn.btn_primary[disabled],
.btn.btn_primary[aria-disabled="true"] {
  color: #fff;
}
.recovery-hint {
  margin: 0;
  font-size: 0.86rem;
  color: #8ea8c9;
  text-align: left;
}
.recovery-hint a {
  color: #9dd4ff;
}
.recovery-hint.is-prominent {
  margin-top: 0.75rem;
  padding: 0.6rem 0.85rem;
  background: rgba(11, 18, 30, 0.6);
  border: 1px solid rgba(122, 157, 201, 0.3);
  border-radius: 8px;
  font-size: 0.93rem;
  color: #c7d6ee;
}
@media (max-width: 640px) {
  .recovery-shell {
    padding: 1.25rem 0.85rem 3rem;
  }
  .recovery-card {
    padding: 1rem;
  }
  .recovery-email-row {
    grid-template-columns: 1fr;
  }
  .recovery-email-form .btn {
    width: 100%;
  }
}
