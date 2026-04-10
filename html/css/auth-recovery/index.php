<?php declare(strict_types=1);

header('Content-Type: text/css; charset=utf-8');
?>
body {
  min-height: 100vh;
  margin: 0;
  background:
    radial-gradient(900px 560px at 0% -15%, rgba(27, 76, 125, 0.9) 0%, transparent 65%),
    radial-gradient(760px 460px at 100% -10%, rgba(43, 72, 106, 0.8) 0%, transparent 60%),
    linear-gradient(180deg, #0b1018 0%, #121b2b 48%, #182335 100%);
  color: #eff5ff;
  font-family: var(--sans-serif);
}
.recovery-shell {
  max-width: 720px;
  margin: 0 auto;
  padding: 2rem 1rem 4rem;
}
.recovery-header {
  margin-bottom: 1.5rem;
}
.recovery-header h1 {
  margin: 0.5rem 0;
  font-size: clamp(2rem, 4vw, 3rem);
  line-height: 1.05;
}
.recovery-header p,
.recovery-card p,
.recovery-status,
.recovery-card label {
  color: #c7d6ee;
}
.recovery-back {
  color: #9dd4ff;
}
.recovery-card {
  background: rgba(15, 22, 36, 0.88);
  border: 1px solid rgba(122, 157, 201, 0.22);
  border-radius: 16px;
  box-shadow: 0 18px 42px rgba(0, 0, 0, 0.35);
  padding: 1.75rem;
}
.recovery-steps {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  list-style: none;
  margin: 0 0 1.25rem;
  padding: 0;
}
.recovery-steps li {
  position: relative;
  border: 1px solid rgba(122, 157, 201, 0.2);
  border-radius: 999px;
  padding: 0.55rem 1rem;
  text-align: center;
  color: #8ea8c9;
  min-width: 7.5rem;
}
.recovery-steps li:not(:last-child)::after {
  content: '>>';
  position: absolute;
  right: -1.45rem;
  top: 50%;
  transform: translateY(-50%);
  color: #7f9ec9;
  font-weight: 700;
}
.recovery-steps li.is-active {
  color: #07131f;
  background: linear-gradient(180deg, #a6dcff 0%, #72bbff 100%);
  border-color: transparent;
  font-weight: 700;
}
.recovery-status {
  margin: 0 auto 1.15rem;
  min-height: 1.35rem;
  max-width: 36rem;
  text-align: center;
}
.recovery-status[data-tone="sent"] {
  color: #08243f;
  background: linear-gradient(180deg, #9fe3ff 0%, #70c6f2 100%);
  border: 1px solid rgba(83, 174, 223, 0.9);
  box-shadow: 0 8px 22px rgba(25, 109, 155, 0.35);
  border-radius: 10px;
  font-weight: 700;
  padding: 0.62rem 0.95rem;
}
.recovery-panel {
  display: grid;
  gap: 1rem;
}
.recovery-panel.is-hidden,
.is-hidden {
  display: none;
}
.recovery-card input {
  width: 100%;
  box-sizing: border-box;
  border-radius: 10px;
  border: 1px solid rgba(122, 157, 201, 0.28);
  background: rgba(11, 18, 30, 0.85);
  color: #eff5ff;
  padding: 0.82rem 0.9rem;
}
.recovery-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.recovery-actions .btn.btn_primary {
  margin-left: auto;
}
.btn.btn_secondary {
  border: 1px solid rgba(122, 157, 201, 0.34);
  background: rgba(14, 21, 34, 0.7);
  color: #dce8fa;
  border-radius: 10px;
  padding: 0.72rem 0.9rem;
  cursor: pointer;
}
.recovery-hint {
  margin: 0.25rem 0 0;
  font-size: 0.88rem;
  color: #8ea8c9;
  text-align: center;
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
    padding: 1.15rem;
  }
  .recovery-steps {
    flex-direction: column;
    align-items: stretch;
  }
  .recovery-steps li:not(:last-child)::after {
    content: 'v';
    right: 50%;
    top: calc(100% + 0.22rem);
    transform: translateX(50%);
  }
}
