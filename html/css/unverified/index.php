<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';

if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

?>@charset "UTF-8";

/**
 * Unverified Page Styles
 */

:root {
  --uv-bg-0: #090d16;
  --uv-bg-1: #0d1322;
  --uv-bg-2: #111a2f;
  --uv-surface: #121b2d;
  --uv-surface-2: #18253d;
  --uv-surface-3: #223253;
  --uv-text-0: #f4f8ff;
  --uv-text-1: #c0cee7;
  --uv-text-2: #93a8cc;
  --uv-line: #2f446a;
  --uv-line-strong: #466294;
  --uv-brand: #6fb7ff;
  --uv-danger: #ff8d8d;
  --uv-success: #8ff5b1;
}

body {
  background:
    radial-gradient(1100px 650px at 0% -20%, #193463 0%, transparent 65%),
    radial-gradient(850px 500px at 100% -15%, #1a2c4f 0%, transparent 60%),
    linear-gradient(180deg, var(--uv-bg-0) 0%, var(--uv-bg-1) 45%, var(--uv-bg-2) 100%);
  color: var(--uv-text-0);
  font-family: var(--sans-serif);
  min-height: 100vh;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
}

header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--uv-line);
}

.header-logo {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--uv-text-0);
  letter-spacing: -0.02em;
  user-select: none;
}

.header-profile {
  position: relative;
}

.profile-btn {
  appearance: none;
  border: 1px solid var(--uv-line);
  background: var(--uv-surface);
  color: var(--uv-text-1);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: border-color 150ms ease, background 150ms ease;
}

.profile-btn:hover {
  border-color: var(--uv-line-strong);
  background: var(--uv-surface-2);
}

.profile-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  background: var(--uv-surface-2);
  border: 1px solid var(--uv-line);
  border-radius: 8px;
  overflow: hidden;
  min-width: 160px;
  display: none;
  z-index: 1000;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.profile-menu.show {
  display: block;
}

.profile-menu a,
.profile-menu button {
  display: block;
  width: 100%;
  padding: 0.75rem 1rem;
  border: 0;
  background: none;
  color: var(--uv-text-1);
  text-align: left;
  cursor: pointer;
  text-decoration: none;
  transition: background 150ms ease, color 150ms ease;
  font-size: 0.9rem;
}

.profile-menu a:hover,
.profile-menu button:hover {
  background: var(--uv-surface-3);
  color: var(--uv-text-0);
}

main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.verification-shell {
  max-width: 420px;
  width: 100%;
}

.verification-card {
  background: linear-gradient(180deg, rgba(24, 36, 59, 0.92) 0%, rgba(19, 29, 47, 0.95) 100%);
  border: 1px solid var(--uv-line);
  box-shadow: 0 18px 40px rgba(2, 6, 14, 0.5);
  border-radius: 12px;
  padding: 2rem 1.5rem;
}

.verification-card h1 {
  margin: 0 0 0.5rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--uv-text-0);
}

.verification-card .description {
  margin: 0 0 1.5rem;
  color: var(--uv-text-1);
  font-size: 0.95rem;
  line-height: 1.4;
}

.verification-message {
  margin: 0 0 1rem;
  padding: 0.68rem 0.78rem;
  border-radius: 9px;
  border: 1px solid transparent;
  font-size: 0.92rem;
  line-height: 1.35;
}

.verification-message-error {
  color: var(--uv-danger);
  border-color: rgba(255, 141, 141, 0.55);
  background: rgba(131, 31, 47, 0.35);
}

.verification-card .email {
  display: block;
  margin: 0 0 1.5rem;
  color: var(--uv-brand);
  font-size: 0.92rem;
  line-height: 1.4;
}

.verification-form section {
  margin-bottom: 1.25rem;
}

.verification-form label {
  display: block;
  margin-bottom: 0.4rem;
  color: var(--uv-text-1);
  font-weight: 700;
  font-size: 0.92rem;
  letter-spacing: 0.01em;
}

.verification-form input {
  width: 100%;
  box-sizing: border-box;
  border-radius: 10px;
  border: 1px solid var(--uv-line);
  background: var(--uv-surface);
  color: var(--uv-text-0);
  padding: 0.7rem 0.8rem;
  outline: none;
  transition: border-color 150ms ease, box-shadow 150ms ease, background 150ms ease;
  font-size: 0.98rem;
}

.verification-form input::placeholder {
  color: var(--uv-text-2);
}

.verification-form input:focus {
  border-color: var(--uv-brand);
  box-shadow: 0 0 0 3px rgba(111, 183, 255, 0.1);
  background: var(--uv-surface-2);
}

.verification-form input:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.verification-form .btn {
  width: 100%;
  margin-bottom: 1rem;
}

.verification-form .status {
  margin-top: 0.5rem;
  font-size: 0.92rem;
  color: var(--uv-text-1);
  text-align: center;
}

.verification-form .status.status-drop-in {
  animation: statusDropIn 260ms cubic-bezier(0.22, 0.61, 0.36, 1);
}

.verification-form .status-error {
  color: var(--uv-danger);
}

.verification-form .status-success {
  color: var(--uv-success);
}

.verification-form .status-info {
  color: var(--uv-text-1);
}

.verification-form .status.is-hidden {
  display: none;
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

.verification-form .resend-link {
  text-align: center;
}

.verification-form .resend-link #resend-email-link {
  color: #9fd1ff;
  text-decoration: none;
  font-size: 0.92rem;
  font-family: inherit;
  border: none;
  background: transparent;
  padding: 0;
  transition: color 150ms ease;
  cursor: pointer;
}

.verification-form .resend-link #resend-email-link.is-working {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.verification-form .resend-link #resend-email-link.is-working::after {
  content: '';
  width: 0.85rem;
  height: 0.85rem;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: verificationResendSpin 700ms linear infinite;
}

.verification-form .resend-link #resend-email-link:hover {
  color: var(--uv-brand);
  text-decoration: underline;
}

@keyframes verificationResendSpin {
  to {
    transform: rotate(360deg);
  }
}

footer {
  border-top: 1px solid var(--uv-line);
  padding: 1.5rem 1rem;
  text-align: center;
  font-size: 0.85rem;
  color: var(--uv-text-2);
}

footer a {
  color: var(--uv-text-1);
  text-decoration: none;
  transition: color 150ms ease;
}

footer a:hover {
  color: var(--uv-brand);
  text-decoration: underline;
}

/* Status message styling */
.status-error {
  color: var(--uv-danger);
}

.status-success {
  color: var(--uv-success);
}

.status-info {
  color: var(--uv-text-1);
}
