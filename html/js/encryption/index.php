<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * PayCal Encryption - ES6 Module
 * Proper module with imports. Async-safe.
 *
 * This file is delivered via PHP for server-side interpolation flexibility.
 * Converted from paycal-encryption.js (git commit 104c47c)
 */

require_once '../../config.php';

// Encryption features are available to all authenticated users
// No auth required for capability detection (public feature)

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

?>

import PW from '/js/phantomwing/';

/**
 * PayCal Encryption - ES6 Module
 * Proper module with imports. Async-safe.
 */

/* ============================================================
 * Capability Detection
 * ============================================================ */

function detectWebCrypto() {
  return !!(
    window.crypto &&
    window.crypto.subtle &&
    typeof window.crypto.subtle.encrypt === "function"
  );
}

async function testAesGcm() {
  if (!detectWebCrypto()) return false;

  try {
    const key = await window.crypto.subtle.generateKey(
      { name: "AES-GCM", length: 256 },
      false,
      ["encrypt", "decrypt"]
    );

    const nonce = window.crypto.getRandomValues(new Uint8Array(12));
    const plaintext = new TextEncoder().encode("test");

    await window.crypto.subtle.encrypt(
      { name: "AES-GCM", iv: nonce },
      key,
      plaintext
    );

    return true;
  } catch (e) {
    PW.warn(`[Encryption] AES-GCM unsupported: ${e.message}`);
    return false;
  }
}

async function testPbkdf2() {
  if (!detectWebCrypto()) return false;

  try {
    const password = new TextEncoder().encode("test");
    const salt = window.crypto.getRandomValues(new Uint8Array(16));

    const baseKey = await window.crypto.subtle.importKey(
        "raw",
        password,
        "PBKDF2",
        false,
        ["deriveKey"]
      );

      await window.crypto.subtle.deriveKey(
        {
          name: "PBKDF2",
          salt,
          iterations: 100000,
          hash: "SHA-256"
        },
        baseKey,
        { name: "AES-GCM", length: 256 },
        false,
        ["encrypt", "decrypt"]
      );

      return true;
    } catch (e) {
      PW.warn(`[Encryption] PBKDF2 unsupported: ${e.message}`);
      return false;
    }
  }

  async function detectAllCapabilities() {
    return {
      webCryptoSupported: detectWebCrypto(),
      aesGcmSupported: await testAesGcm(),
      pbkdf2Supported: await testPbkdf2(),
      userAgent: navigator.userAgent,
      timestamp: Date.now()
    };
  }

  /* ============================================================
   * Decrypt / Fallback
   * ============================================================ */

  async function decryptOrFallback(entry, userKey) {
    if (
      entry &&
      entry.encrypted_blob &&
      typeof entry.encrypted_blob === "string"
    ) {

      if (!userKey) {
        PW.warn('[PayCal] No user key. Falling back.');
        return entry;
      }

      try {
        const envelope = JSON.parse(atob(entry.encrypted_blob));

        if (!envelope.ciphertext || !envelope.nonce) {
          throw new Error("Malformed envelope");
        }

        const ciphertext = Uint8Array.from(
          atob(envelope.ciphertext),
          c => c.charCodeAt(0)
        );

        const nonce = Uint8Array.from(
          atob(envelope.nonce),
          c => c.charCodeAt(0)
        );

        const aad = envelope.aad
          ? new TextEncoder().encode(envelope.aad)
          : undefined;

        const decrypted = await window.crypto.subtle.decrypt(
          {
            name: "AES-GCM",
            iv: nonce,
            additionalData: aad
          },
          userKey,
          ciphertext
        );

        const decoded = new TextDecoder().decode(decrypted);
        const result = JSON.parse(decoded);

        return result;
      } catch (err) {
        PW.warn(`[PayCal] Decryption failed: ${err.message}`);
        return entry;
      }
    }

    return entry;
  }

  /* ============================================================
   * Public API (Global Namespace + Module Export)
   * ============================================================ */

  window.PayCalEncryption = {
    detectAllCapabilities,
    decryptOrFallback
  };

  /* ============================================================
   * Auto Init (Optional)
   * ============================================================ */

  document.addEventListener("DOMContentLoaded", async () => {

    const report = await detectAllCapabilities();
    // Example: Log detected capabilities to console
    PW.log(`[PayCalEncryption] Capabilities: webCrypto=${report.webCryptoSupported}, aesGcm=${report.aesGcmSupported}, pbkdf2=${report.pbkdf2Supported}`);
  });

  /* ============================================================
   * Module Export
   * ============================================================ */

  export default {
    detectAllCapabilities,
    decryptOrFallback,
    detectWebCrypto,
    testAesGcm,
    testPbkdf2
  };
