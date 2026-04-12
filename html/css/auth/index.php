<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

  :root {
    --bg-0: #1b1b1b;
    --bg-1: #202020;
    --bg-2: #2b2b2b;
    --surface: #2d2d2d;
    --surface-2: #383838;
    --surface-3: #444444;
    --text-0: #ffffff;
    --text-1: #cccccc;
    --text-2: #999999;
    --line: rgba(255, 255, 255, 0.10);
    --line-strong: rgba(255, 255, 255, 0.24);
    --brand: #0078D7;
    --brand-ink: #ffffff;
    --danger: #ffb0b7;
    --danger-bg: rgba(131, 31, 47, 0.34);
    --success: #6dcf8b;
    --success-bg: rgba(24, 100, 57, 0.34);
  }

  body {
    background:
      radial-gradient(1100px 650px at 0% -20%, rgba(0, 120, 215, 0.18) 0%, transparent 65%),
      radial-gradient(850px 500px at 100% -15%, rgba(0, 120, 215, 0.10) 0%, transparent 60%),
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
    align-items: flex-start;
    justify-content: center;
    min-height: calc(100vh - 6rem);
    padding: 0;
  }

  .auth-shell {
    max-width: 100%;
    width: 100%;
  }

  .auth-layout {
    display: grid;
    grid-template-columns: 1fr minmax(320px, 460px);
    gap: 1rem;
    align-items: start;
  }

  .auth-hero {
    position: relative;
    min-height: 0;
    aspect-ratio: 16 / 9;
    border-radius: 28px;
    overflow: hidden;
    border: 1px solid rgba(0, 120, 215, 0.28);
    box-shadow: 0 28px 64px rgba(0, 0, 0, 0.34), 0 0 0 1px rgba(0, 120, 215, 0.10);
    background: #1b1b1b;
  }

  .auth-hero-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
  }

  .auth-hero-overlay {
    position: absolute;
    inset: 0;
    background:
      linear-gradient(0deg, rgba(0, 30, 60, 0.68) 0%, transparent 36%, transparent 72%, rgba(0, 30, 60, 0.42) 100%),
      linear-gradient(90deg, rgba(0, 120, 215, 0.08) 0%, transparent 50%);
  }

  .auth-hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    min-height: 100%;
    padding: 1.5rem;
  }

  .auth-hero-note {
    display: inline-flex;
    width: fit-content;
    margin: 0;
    padding: 0.5rem 0.78rem;
    border-radius: 999px;
    border: 1px solid rgba(0, 120, 215, 0.32);
    background: rgba(0, 30, 60, 0.58);
    backdrop-filter: blur(14px);
    color: rgba(243, 247, 253, 0.85);
    font-size: 0.78rem;
    letter-spacing: 0.04em;
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
    background: linear-gradient(180deg, rgba(45, 45, 45, 0.94) 0%, rgba(35, 35, 35, 0.98) 100%);
    border: 1px solid var(--line);
    box-shadow: 0 24px 56px rgba(0, 0, 0, 0.34);
    border-radius: 28px;
    padding: 1.25rem 1.25rem 0;
    backdrop-filter: blur(18px);
  }

  .auth-tabs-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.1rem;
  }

  .auth-tabs {
    display: inline-flex;
    background: rgba(27, 27, 27, 0.76);
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
    color: #ffffff;
    background: linear-gradient(180deg, #1a8fe8 0%, var(--brand) 100%);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.26);
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
    border-radius: 16px;
    background: rgba(45, 45, 45, 0.74);
    padding: 0.95rem 1rem;
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
    color: #60cdff;
    text-underline-offset: 2px;
  }

  .auth-panel input[type="email"],
  .auth-panel input[type="password"],
  .auth-panel input[type="text"] {
    width: 100%;
    box-sizing: border-box;
    border-radius: 14px;
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
    background: linear-gradient(180deg, #1a8fe8 0%, var(--brand) 100%);
    color: var(--brand-ink);
    font-weight: 800;
    border-radius: 14px;
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
    background: rgba(45, 45, 45, 0.6);
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
    color: #60cdff;
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
      padding: 1.15rem 1rem 0;
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
    .auth-container {
      padding: 1rem;
    }

    .auth-layout {
      grid-template-columns: 1fr;
    }

    .auth-hero {
      min-height: 280px;
      aspect-ratio: 16 / 9;
    }

    .auth-hero-image {
      object-position: left center;
    }

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
