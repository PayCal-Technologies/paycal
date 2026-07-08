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
    --text-2: var(--color-text-muted, #6c7b86);
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

  .auth-shell.is-signin-only {
    max-width: 1120px;
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
    font-size: var(--text, 1.125rem);
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
    font-size: var(--text, 1.125rem);
    line-height: 1.35;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.38);
    opacity: 0;
    pointer-events: none;
    transform: translateX(-50%);
    transition: opacity 180ms ease;
  }

  .auth-feedback-banner.show {
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

  .auth-feedback-banner-actions {
    margin-top: 0.6rem;
    display: flex;
    gap: 0.45rem;
    align-items: center;
  }

  .auth-feedback-banner-input {
    flex: 1 1 auto;
    min-width: 0;
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 8px;
    background: rgba(10, 10, 10, 0.35);
    color: #ffffff;
    padding: 0.45rem 0.55rem;
    font: inherit;
    line-height: 1.2;
  }

  .auth-feedback-banner-input::placeholder {
    color: rgba(255, 255, 255, 0.75);
  }

  .auth-feedback-banner-input:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.92);
    outline-offset: 1px;
  }

  .auth-feedback-banner-btn {
    appearance: none;
    border: 1px solid rgba(255, 255, 255, 0.45);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    padding: 0.45rem 0.65rem;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
  }

  .auth-feedback-banner-btn:hover,
  .auth-feedback-banner-btn:focus-visible {
    background: rgba(255, 255, 255, 0.26);
  }

  .auth-card {
    background: linear-gradient(180deg, rgba(45, 45, 45, 0.94) 0%, rgba(35, 35, 35, 0.98) 100%);
    border: 1px solid var(--line);
    box-shadow: 0 24px 56px rgba(0, 0, 0, 0.34);
    border-radius: 28px;
    padding: 1.25rem 1.25rem 0;
    backdrop-filter: blur(18px);
  }

  .auth-card-heading {
    margin-bottom: 1rem;
  }

  .auth-card-heading h2 {
    margin: 0;
    color: var(--text-0);
    font-size: 1.35rem;
    line-height: 1.16;
    letter-spacing: 0 !important;
  }

  .auth-tabs-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.1rem;
    width: 100%;
    min-width: 0;
  }

  .auth-tabs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    width: 100%;
    max-width: 100%;
    min-width: 0;
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
    min-width: 0;
    min-height: 2.25rem;
    font-weight: 700;
    font-size: clamp(0.82rem, 2.7vw, 1rem) !important;
    line-height: 1.12 !important;
    letter-spacing: 0 !important;
    text-align: center;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
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
  }

  .auth-shell.is-register .auth-track {
    transform: translateX(-50%);
  }

  .auth-shell.is-signin-only .auth-track {
    width: 100%;
    transform: none;
  }

  .auth-panel {
    width: 50%;
    box-sizing: border-box;
    padding: 0 0 2rem 0;
  }

  .auth-shell.is-signin-only .auth-panel,
  .auth-create-form.auth-panel {
    width: 100%;
  }

  .auth-create-form.auth-panel {
    padding: 0;
  }

  .auth-panel .btn {
    width: 100%;
    min-width: 0;
    letter-spacing: 0 !important;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    text-align: center;
  }

  .auth-panel .status {
    margin-top: 0.5rem;
    font-size: var(--text, 1.125rem);
    color: var(--text-1);
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

  .auth-panel section {
    margin-bottom: 1.35rem;
  }

  .auth-panel label {
    display: block;
    margin-bottom: 0.2rem;
    color: var(--text-1);
    font-weight: 700;
    font-size: var(--text, 1.125rem);
    letter-spacing: 0.01em;
  }

  .auth-panel p {
    color: var(--text-1);
    font-size: var(--text, 1.125rem);
    line-height: 1.45;
    margin: 0.5rem 0;
  }

  .auth-panel .divider-or {
    text-align: center;
  }

  .federated-signin[hidden] {
    display: none;
  }

  .federated-signin {
    margin-top: 0;
  }

  .federated-signin-providers {
    display: grid;
    gap: 0.55rem;
  }

  .federated-signin-button {
    position: relative;
    width: 100%;
    min-height: 2.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--line-strong);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.94);
    color: #1f1f1f;
    padding: 0.58rem 0.82rem;
    font: inherit;
    font-weight: 800;
    line-height: 1.15 !important;
    letter-spacing: 0 !important;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    cursor: pointer;
    text-align: center;
  }

  .federated-signin-button:hover {
    background: #ffffff;
  }

  .federated-signin-button:focus-visible {
    outline: 2px solid var(--color-focus-ring, #0096d6);
    outline-offset: 2px;
  }

  .federated-signin-icon {
    position: absolute;
    left: 0.82rem;
    width: 1.8rem;
    height: 1.8rem;
    display: inline-grid;
    place-items: center;
    border-radius: 50%;
    background: #f2f2f2;
    color: #1f1f1f;
    font-weight: 900;
    line-height: 1;
  }

  .federated-signin-text {
    min-width: 0;
    text-align: center;
    letter-spacing: 0 !important;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
  }

  .auth-panel .auth-recover-link {
    text-align: center;
    margin: 0.65rem 0 0;
  }

  .auth-account-switch {
    display: block;
    margin: 0.65rem 0 0;
    text-align: center;
    color: var(--text-1);
    font-weight: 700;
  }

  .auth-account-switch a,
  a.auth-account-switch {
    color: #60cdff;
    text-decoration: underline;
    text-underline-offset: 2px;
  }

  .auth-account-switch a:hover,
  .auth-account-switch a:focus-visible,
  a.auth-account-switch:hover,
  a.auth-account-switch:focus-visible {
    color: #60cdff;
    text-decoration-thickness: 1px;
  }

  .auth-panel .auth-recover-link a {
    text-decoration: underline;
    text-underline-offset: 2px;
  }

  .auth-panel a {
    color: #60cdff;
    padding: 0;
    text-underline-offset: 2px;
  }

  .auth-panel a:hover,
  .auth-panel a:active,
  .auth-panel a:focus-visible,
  .auth-panel .btn-link:hover,
  .auth-panel .btn-link:active,
  .auth-panel .btn-link:focus-visible {
    background: transparent;
    color: #60cdff;
    text-decoration: underline;
    text-decoration-thickness: 1px;
    text-underline-offset: 2px;
  }

  .auth-panel input[type="email"],
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
    font-size: var(--text, 1.125rem);
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
    line-height: 1.18 !important;
    letter-spacing: 0 !important;
    border-radius: 14px;
    padding: 0.72rem 0.9rem;
    cursor: pointer;
  }

  .auth-panel .btn.btn_secondary {
    border: 1px solid var(--line-strong);
    background: rgba(255, 255, 255, 0.06);
    color: var(--text-0);
    font-weight: 700;
    line-height: 1.18 !important;
    letter-spacing: 0 !important;
    border-radius: 14px;
    padding: 0.72rem 0.9rem;
    cursor: pointer;
    margin-top: 0.55rem;
  }

  .auth-panel .btn.btn_secondary:hover,
  .auth-panel .btn.btn_secondary:focus-visible {
    background: rgba(255, 255, 255, 0.12);
  }

  .auth-signin-primary {
    display: grid;
    gap: 0;
  }

  .auth-signin-divider {
    border: 0;
    border-top: 1px solid var(--line);
    margin: 1.1rem 0 0.65rem;
  }

  .auth-signin-notice {
    margin-top: 0.55rem;
    padding: 0.65rem 0.75rem;
    border-radius: 10px;
    border: 1px solid var(--line);
    background: rgba(45, 45, 45, 0.55);
    color: var(--text-1);
    font-size: 0.98rem;
    line-height: 1.4;
  }

  .auth-signin-notice.is-security {
    border-color: #93455a;
    background: var(--danger-bg);
    color: var(--danger);
  }

  .auth-signin-notice[hidden],
  .auth-signin-error-actions[hidden] {
    display: none;
  }

  .auth-signin-notice-title {
    display: block;
    font-weight: 800;
    margin-bottom: 0.25rem;
  }

  .auth-signin-error-actions {
    margin-top: 0.55rem;
    display: grid;
    gap: 0.45rem;
  }

  .auth-signin-error-actions .btn {
    width: 100%;
  }

  .auth-signin-error-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.75rem;
    justify-content: center;
    margin-top: 0.15rem;
  }

  .auth-signin-error-links .btn-link {
    font-size: 0.95rem;
  }

  .auth-feedback-banner.notice {
    border-color: var(--line-strong);
    background: rgba(45, 45, 45, 0.95);
    color: var(--text-0);
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
    line-height: 1.25 !important;
    letter-spacing: 0 !important;
    color: #60cdff;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 2px;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    -webkit-tap-highlight-color: transparent;
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
      padding: 0.48rem 0.72rem;
    }
  }

  @media (max-width: 900px) {
    .auth-container {
      padding: 5px;
      min-width: 0;
      box-sizing: border-box;
    }

    .auth-shell,
    .auth-layout,
    .auth-hero,
    .auth-card,
    .auth-viewport {
      width: 100%;
      max-width: 100%;
      min-width: 0;
      box-sizing: border-box;
    }

    .auth-layout {
      grid-template-columns: minmax(0, 1fr);
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
      padding: 0.42rem 0.56rem !important;
      min-height: 2.35rem;
      line-height: 1.08;
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

  .auth-shell[data-signup-preview-variant="light"] {
    --bg-0: #ffffff;
    --bg-1: #f5f7fb;
    --bg-2: #e9eef6;
    --surface: #ffffff;
    --surface-2: #f7faff;
    --surface-3: #eef3fb;
    --text-0: #17202a;
    --text-1: #374151;
    --text-2: #64748b;
    --line: rgba(30, 41, 59, 0.16);
    --line-strong: rgba(30, 41, 59, 0.32);
    --brand: var(--accent-color, #00508f);
    --brand-ink: var(--accent-contrast-color, #ffffff);
  }

  .auth-shell[data-signup-preview-variant="dark"] {
    --brand: var(--accent-color, #00508f);
    --brand-ink: var(--accent-contrast-color, #ffffff);
  }

  .auth-signup-personalization {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.35rem;
  }

  .auth-signup-progress {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.35rem;
  }

  .auth-signup-progress span,
  .auth-signup-progress button {
    appearance: none;
    min-height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--line);
    border-radius: 999px;
    background: color-mix(in srgb, var(--surface) 84%, transparent);
    color: var(--text-1);
    font: inherit;
    font-size: 0.86rem;
    font-weight: 700;
    line-height: 1.1;
    text-align: center;
    padding: 0.35rem 0.45rem;
    cursor: pointer;
  }

  .auth-signup-progress span.is-active,
  .auth-signup-progress button.is-active {
    border-color: color-mix(in srgb, var(--accent-color, var(--brand)) 72%, var(--line));
    background: color-mix(in srgb, var(--accent-color, var(--brand)) 18%, var(--surface));
    color: var(--text-0);
  }

  .auth-signup-progress button.is-complete {
    color: var(--text-0);
    border-color: color-mix(in srgb, var(--accent-color, var(--brand)) 52%, var(--line));
  }

  .auth-signup-progress button:focus-visible {
    outline: 2px solid var(--color-focus-ring, #0096d6);
    outline-offset: 2px;
  }

  .auth-signup-personalization h2 {
    margin: 0;
    color: var(--text-0);
    font-size: 1.28rem;
    line-height: 1.16;
    letter-spacing: 0 !important;
  }

  .auth-signup-intro {
    margin: -0.65rem 0 0;
    color: var(--text-1);
  }

  .auth-signup-group {
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 0.85rem;
    display: grid;
    gap: 0.82rem;
    background: color-mix(in srgb, var(--surface) 76%, transparent);
  }

  .auth-signup-group legend {
    padding: 0 0.3rem;
    color: var(--text-0);
    font-weight: 800;
    letter-spacing: 0 !important;
  }

  .auth-tier-options {
    display: grid;
    gap: 0.55rem;
  }

  .auth-tier-card {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 0.18rem 0.55rem;
    align-items: start;
    min-width: 0;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--surface);
    color: var(--text-1);
    padding: 0.68rem;
    cursor: pointer;
  }

  .auth-tier-card input {
    margin-top: 0.18rem;
    accent-color: var(--accent-color, var(--brand));
  }

  .auth-tier-card-body {
    display: grid;
    grid-column: 2;
    gap: 0.24rem;
    min-width: 0;
  }

  .auth-tier-card-title {
    color: var(--text-0);
    font-weight: 800;
    line-height: 1.16;
  }

  .auth-tier-card-copy {
    color: var(--text-1);
    font-size: 0.95rem;
    line-height: 1.35;
  }

  .auth-tier-card-price {
    color: var(--text-0);
    font-size: 1.02rem;
    font-weight: 900;
    line-height: 1.15;
  }

  .auth-tier-card-price span {
    color: var(--text-2);
    font-size: 0.86rem;
    font-weight: 700;
  }

  .auth-tier-card-badge {
    width: fit-content;
    border: 1px solid color-mix(in srgb, var(--accent-color, var(--brand)) 52%, var(--line));
    border-radius: 999px;
    color: var(--text-0);
    background: color-mix(in srgb, var(--accent-color, var(--brand)) 14%, var(--surface));
    padding: 0.18rem 0.48rem;
    font-size: 0.82rem;
    font-weight: 800;
    line-height: 1.2;
  }

  .auth-tier-card.is-selected,
  .auth-tier-card:focus-within {
    border-color: color-mix(in srgb, var(--accent-color, var(--brand)) 72%, var(--line));
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent-color, var(--brand)) 20%, transparent);
  }

  .auth-signup-control {
    display: grid;
    gap: 0.4rem;
  }

  .auth-signup-control-label {
    color: var(--text-1);
    font-weight: 800;
  }

  .auth-segmented {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(88px, 1fr));
    gap: 0.35rem;
  }

  .auth-segmented label {
    position: relative;
    margin: 0;
  }

  .auth-segmented input {
    position: absolute;
    inset: 0;
    opacity: 0;
  }

  .auth-segmented span {
    min-height: 2.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    border: 1px solid var(--line);
    border-radius: 999px;
    background: var(--surface);
    color: var(--text-1);
    padding: 0.42rem 0.6rem;
    font-size: 0.95rem;
    font-weight: 800;
    line-height: 1.12;
    text-align: center;
  }

  .auth-segmented input:checked + span {
    border-color: color-mix(in srgb, var(--accent-color, var(--brand)) 76%, var(--line));
    background: color-mix(in srgb, var(--accent-color, var(--brand)) 18%, var(--surface));
    color: var(--text-0);
  }

  .auth-segmented label:focus-within span {
    outline: 2px solid var(--color-focus-ring, #0096d6);
    outline-offset: 2px;
  }

  .auth-accent-options {
    display: grid;
    grid-template-columns: repeat(6, minmax(34px, 1fr));
    gap: 0.42rem;
  }

  .auth-accent-swatch {
    position: relative;
    min-width: 0;
    aspect-ratio: 1;
    border: 2px solid var(--line-strong);
    border-radius: 999px;
    cursor: pointer;
    padding: 0;
    box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.34);
  }

  .auth-accent-swatch span {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  .auth-accent-swatch.is-selected,
  .auth-accent-swatch:focus-visible {
    border-color: var(--text-0);
    outline: 2px solid var(--color-focus-ring, #0096d6);
    outline-offset: 2px;
  }

  .auth-signup-grid {
    display: grid;
    gap: 0.35rem;
  }

  .auth-signup-grid label {
    margin: 0;
  }

  .auth-signup-grid label span {
    color: var(--text-2);
    font-weight: 600;
  }

  .auth-panel select,
  .auth-signup-grid input[type="text"] {
    width: 100%;
    box-sizing: border-box;
    border-radius: 14px;
    border: 1px solid var(--line);
    background: var(--surface);
    color: var(--text-0);
    padding: 0.7rem 0.8rem;
    outline: none;
    font: inherit;
    font-size: var(--text, 1.125rem);
  }

  .auth-panel select:focus-visible,
  .auth-signup-grid input[type="text"]:focus-visible {
    outline: 2px solid var(--color-focus-ring, #0096d6);
    outline-offset: 2px;
    border-color: var(--line-strong);
  }

  .auth-signup-preview {
    display: grid;
    gap: 0.58rem;
    border: 1px solid color-mix(in srgb, var(--accent-color, var(--brand)) 48%, var(--line));
    border-radius: 16px;
    background: color-mix(in srgb, var(--accent-color, var(--brand)) 12%, var(--surface));
    color: var(--text-0);
    padding: 0.9rem;
  }

  .auth-signup-preview-kicker {
    margin: 0;
    color: var(--text-1);
    font-weight: 800;
  }

  .auth-signup-preview h3 {
    margin: 0;
    color: var(--text-0);
    font-size: 1.08rem;
    line-height: 1.2;
    letter-spacing: 0 !important;
  }

  .auth-signup-preview h2 {
    margin: 0;
    color: var(--text-0);
    font-size: 1.16rem;
    line-height: 1.2;
    letter-spacing: 0 !important;
  }

  .auth-signup-preview p {
    margin: 0;
  }

  .auth-signup-preview ul {
    margin: 0;
    padding-left: 1.1rem;
    color: var(--text-1);
  }

  .auth-signup-preview-calendar {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.25rem;
    min-width: 0;
    border-top: 1px solid var(--line);
    padding-top: 0.65rem;
  }

  .auth-signup-preview-calendar > * {
    min-height: 1.72rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: color-mix(in srgb, var(--surface) 72%, transparent);
    color: var(--text-1);
    font-size: 0.82rem;
    line-height: 1;
  }

  .auth-signup-preview-calendar strong {
    background: color-mix(in srgb, var(--accent-color, var(--brand)) 24%, var(--surface));
    color: var(--text-0);
  }

  .auth-create-container {
    align-items: flex-start;
    padding: 0 1rem 2rem;
  }

  .auth-create-shell {
    max-width: 1180px;
    width: 100%;
  }

  .auth-create-shell,
  .auth-create-shell * {
    letter-spacing: 0 !important;
    word-spacing: 0 !important;
  }

  .auth-create-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 380px);
    gap: 1rem;
    align-items: start;
  }

  .auth-create-card {
    padding: 1.25rem;
  }

  .auth-create-header {
    display: grid;
    gap: 0.35rem;
    margin-bottom: 1rem;
  }

  .auth-create-kicker {
    margin: 0;
    color: var(--text-2);
    font-size: 0.86rem;
    font-weight: 900;
    letter-spacing: 0.04em !important;
    text-transform: uppercase;
  }

  .auth-create-header h1 {
    margin: 0;
    color: var(--text-0);
    font-size: 1.8rem;
    line-height: 1.08;
    letter-spacing: 0 !important;
  }

  .auth-create-header p {
    margin: 0;
    color: var(--text-1);
    line-height: 1.45;
  }

  .auth-create-header .auth-account-switch {
    margin-top: 0.25rem;
    text-align: left;
  }

  .auth-signup-step {
    display: grid;
    gap: 1rem;
  }

  .auth-signup-step[hidden] {
    display: none !important;
  }

  .auth-signup-step-heading {
    display: grid;
    gap: 0.35rem;
  }

  .auth-signup-step-heading .auth-signup-intro {
    margin: 0;
  }

  .auth-signup-step-heading h2 {
    margin: 0;
    color: var(--text-0);
    font-size: 1.28rem;
    line-height: 1.16;
    letter-spacing: 0 !important;
  }

  .auth-signup-step-heading h2:focus-visible {
    outline: 2px solid var(--color-focus-ring, #0096d6);
    outline-offset: 3px;
    border-radius: 6px;
  }

  .auth-step-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.6rem;
  }

  .auth-step-actions .btn:only-child {
    grid-column: 2;
  }

  .auth-step-actions .btn.btn_secondary {
    margin-top: 0;
  }

  .auth-create-preview {
    position: sticky;
    top: 1rem;
  }

  @media (max-width: 560px) {
    .auth-create-card {
      padding: 1rem;
    }

    .auth-create-header h1 {
      font-size: 1.5rem;
    }

    .auth-signup-progress {
      grid-template-columns: 1fr;
    }

    .auth-step-actions {
      grid-template-columns: 1fr;
    }

    .auth-step-actions .btn:only-child {
      grid-column: auto;
    }
  }

  @media (max-width: 900px) {
    .auth-create-container {
      padding: 2.6rem 5px 5px;
    }

    .auth-create-layout {
      grid-template-columns: minmax(0, 1fr);
    }

    .auth-create-preview {
      position: static;
    }
  }
