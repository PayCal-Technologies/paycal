<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

  :root {
    --bg-0: #090d16;
    --bg-1: #0d1322;
    --bg-2: #111a2f;
    --surface: #121b2d;
    --surface-2: #18253d;
    --surface-3: #223253;
    --text-0: #f4f8ff;
    --text-1: #c0cee7;
    --text-2: #93a8cc;
    --line: #2f446a;
    --line-strong: #466294;
    --brand: #6fb7ff;
    --brand-ink: #041429;
    --danger: #ff8d8d;
    --danger-bg: rgba(131, 31, 47, 0.4);
    --success: #8ff5b1;
    --success-bg: rgba(24, 100, 57, 0.38);
  }

  body {
    background:
      radial-gradient(1100px 650px at 0% -20%, #193463 0%, transparent 65%),
      radial-gradient(850px 500px at 100% -15%, #1a2c4f 0%, transparent 60%),
      linear-gradient(180deg, var(--bg-0) 0%, var(--bg-1) 45%, var(--bg-2) 100%);
    color: var(--text-0);
    font-family: var(--sans-serif);
    min-height: 100vh;
    margin: 0;
    padding: 0;
  }

  .auth-header {
    padding: 1rem 1rem;
    display: flex;
    align-items: center;
  }

  .auth-logo {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-0);
    letter-spacing: -0.02em;
    user-select: none;
  }

  .auth-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 6rem);
    padding: 0.25rem;
  }

  .auth-shell {
    max-width: 460px;
    width: 100%;
  }

  .auth-message {
    margin: 0 0 0.75rem;
    padding: 0.75rem;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 0.95rem;
    line-height: 1.35;
  }

  .auth-message.error {
    color: var(--danger);
    border-color: #93455a;
    background: var(--danger-bg);
  }

  .auth-message.success {
    color: var(--success);
    border-color: #3f7b59;
    background: var(--success-bg);
  }

  .auth-feedback-banner {
    position: fixed;
    top: 0.9rem;
    left: 50%;
    transform: translate(-50%, -140%);
    z-index: 2200;
    width: min(92vw, 520px);
    border-radius: 12px;
    border: 1px solid #93455a;
    background: rgba(131, 31, 47, 0.95);
    color: #ffe8ec;
    padding: 0.72rem 0.9rem;
    font-size: 0.94rem;
    line-height: 1.35;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.38);
    opacity: 0;
    pointer-events: none;
    transition: transform 180ms ease, opacity 180ms ease;
  }

  .auth-feedback-banner.show {
    transform: translate(-50%, 0);
    opacity: 1;
    pointer-events: auto;
  }

  .auth-feedback-banner.success {
    border-color: #3f7b59;
    background: rgba(24, 100, 57, 0.95);
    color: #e8fff0;
  }

  .auth-feedback-banner a {
    color: #ffffff;
    text-decoration: underline;
    text-underline-offset: 2px;
    font-weight: 600;
  }

  .auth-feedback-banner a:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.9);
    outline-offset: 2px;
    border-radius: 4px;
  }

  .auth-card {
    background: linear-gradient(180deg, rgba(24, 36, 59, 0.92) 0%, rgba(19, 29, 47, 0.95) 100%);
    border: 1px solid var(--line);
    box-shadow: 0 18px 40px rgba(2, 6, 14, 0.5);
    border-radius: 12px;
    padding: 1rem 1rem 0;
  }

  .auth-tabs-wrapper {
    text-align: center;
    margin-bottom: 1rem;
  }

  .auth-beta-notice {
    text-align: center;
    margin: -0.5rem 0 0.75rem;
    font-size: 0.7rem;
    letter-spacing: 0.04em;
    color: var(--text-2, rgba(255,255,255,0.38));
  }

  .auth-tabs {
    display: inline-flex;
    background: rgba(11, 20, 35, 0.6);
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 0.25rem;
    gap: 0.25rem;
  }

  .auth-tab {
    appearance: none;
    border: 0;
    border-radius: 999px;
    padding: 0.25rem 1rem;
    font-weight: 700;
    cursor: pointer;
    background: transparent;
    color: var(--text-1);
    transition: color 180ms ease, background 180ms ease;
  }

  .auth-tab.active {
    color: var(--text-0);
    background: linear-gradient(180deg, #223658 0%, #1a2c49 100%);
    box-shadow: 0 4px 14px rgba(4, 10, 20, 0.45);
  }

  .auth-viewport {
    overflow: hidden;
  }

  .auth-track {
    display: flex;
    width: 200%;
    transform: translateX(0);
    transition: transform 300ms cubic-bezier(0.22, 0.61, 0.36, 1);
  }

  .auth-shell.is-register .auth-track {
    transform: translateX(-50%);
  }

  .auth-panel {
    width: 50%;
    box-sizing: border-box;
    padding: 0 0 2rem 0;
  }

  .auth-panel .btn {
    width: 100%;
  }

  .auth-panel .status {
    margin-top: 0.5rem;
    font-size: 0.92rem;
    color: var(--text-1);
  }

  .auth-panel .status.status-drop-in {
    animation: statusDropIn 260ms cubic-bezier(0.22, 0.61, 0.36, 1);
  }

  .auth-verification-panel {
    margin: 0 0 1rem;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: rgba(15, 26, 44, 0.6);
    padding: 0.85rem 0.9rem;
  }

  .auth-verification-title {
    margin: 0;
    color: var(--text-0);
    font-size: 1.05rem;
  }

  .auth-verification-message {
    margin: 0.45rem 0 0;
    color: var(--text-1);
  }

  .auth-verification-list {
    margin: 0.6rem 0 0;
    padding-left: 1.15rem;
    color: var(--text-1);
  }

  .auth-verification-list li {
    margin: 0.16rem 0;
  }

  @keyframes statusDropIn {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .auth-panel section {
    margin-bottom: 1.35rem;
  }

  .auth-panel label {
    display: block;
    margin-bottom: 0.2rem;
    color: var(--text-1);
    font-weight: 700;
    font-size: 0.92rem;
    letter-spacing: 0.01em;
  }

  .auth-panel p {
    color: var(--text-1);
    line-height: 1.45;
    margin: 0.5rem 0;
  }

  .auth-panel .divider-or {
    text-align: center;
  }

  .auth-panel a {
    color: #9fd1ff;
    text-underline-offset: 2px;
  }

  .auth-panel input[type="email"],
  .auth-panel input[type="password"],
  .auth-panel input[type="text"] {
    width: 100%;
    box-sizing: border-box;
    border-radius: 10px;
    border: 1px solid var(--line);
    background: var(--surface);
    color: var(--text-0);
    padding: 0.7rem 0.8rem;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease, background 150ms ease;
    font-size: 0.98rem;
  }

  .auth-panel input::placeholder {
    color: var(--text-2);
  }

  .auth-panel input:focus {
    border-color: var(--line-strong);
    box-shadow: 0 0 0 3px rgba(79, 136, 209, 0.28);
    background: var(--surface-2);
  }

  .auth-panel input:focus-visible {
    outline: 2px solid var(--color-focus-ring, #0096d6);
    outline-offset: 2px;
  }

  .auth-panel .btn.btn_primary {
    border: 1px solid transparent;
    background: linear-gradient(180deg, #9bd0ff 0%, var(--brand) 100%);
    color: var(--brand-ink);
    font-weight: 800;
    border-radius: 10px;
    padding: 0.72rem 0.9rem;
    cursor: pointer;
  }

  .auth-panel .btn.btn_primary:hover {
    filter: brightness(1.04);
  }

  .auth-verify-container {
    align-items: flex-start;
    padding-top: 3rem;
  }

  .auth-verify-shell {
    max-width: 560px;
  }

  .auth-verify-card {
    padding: 1.35rem 1.25rem 1.25rem;
  }

  .auth-verify-title {
    margin: 0 0 0.55rem;
    color: var(--text-0);
    font-size: 1.6rem;
    letter-spacing: -0.01em;
  }

  .auth-verify-message {
    margin: 0;
    color: var(--text-1);
    line-height: 1.45;
  }

  .auth-verify-next {
    margin-top: 1rem;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: rgba(15, 26, 44, 0.6);
    padding: 0.9rem;
  }

  .auth-verify-next-title {
    margin: 0 0 0.55rem;
    font-size: 1.02rem;
    color: var(--text-0);
  }

  .auth-verify-list {
    margin: 0;
    padding-left: 1.15rem;
    color: var(--text-1);
  }

  .auth-verify-list li {
    margin: 0.2rem 0;
  }

  .auth-verify-actions {
    margin-top: 1rem;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.6rem;
  }

  .auth-verify-actions .btn {
    width: 100%;
    text-align: center;
  }

  .btn-link {
    background: transparent;
    border: 0;
    padding: 0;
    margin: 0;
    font: inherit;
    color: #9fd1ff;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 2px;
  }

  @media (max-width: 560px) {
    .auth-header {
      padding: 1rem 1.5rem;
    }

    .auth-logo {
      font-size: 1.35rem;
    }

    .auth-shell {
      max-width: 100%;
    }

    .auth-card {
      padding: 1.5rem 1.15rem 0;
    }

    .auth-verify-card {
      padding: 1.2rem 1rem;
    }

    .auth-panel {
      padding: 0 0 1.5rem 0;
    }

    .auth-verify-actions {
      grid-template-columns: 1fr;
    }

    .auth-tab {
      padding: 0.48rem 0.82rem;
    }
  }

  @media (max-width: 900px) {
    .auth-tabs {
      padding: 0.25rem;
      gap: 0.25rem;
    }

    .auth-tab {
      padding: 0.4rem 0.9rem !important;
      min-height: 2rem;
      line-height: 1.1;
    }

    .auth-panel .btn.btn_primary {
      padding: 0.72rem 0.9rem !important;
      min-height: 2.4rem;
      line-height: 1.15;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .auth-track {
      transition: none;
    }
  }
