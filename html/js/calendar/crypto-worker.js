self.cryptoState = {
  dek: null,
  dekRaw: null,
  dekVersion: 1,
  cryptoVersion: 1,
  sessionDiagnosticToken: null,  // Session-based token, not persistent fingerprint
};

const encoder = new TextEncoder();
const decoder = new TextDecoder();

/**
 * Generate a one-time session diagnostic token.
 * This replaces the persistent fingerprint with a per-session random token.
 * Token is discarded on DEK lock, creating no long-term correlation.
 */
function generateSessionDiagnosticToken() {
  const randomBytes = crypto.getRandomValues(new Uint8Array(32));
  return bytesToB64(randomBytes);
}

function safeFingerprint(value) {
  if (!value || typeof value !== 'string') {
    return '';
  }

  let hash = 5381;
  for (let i = 0; i < value.length; i += 1) {
    hash = ((hash << 5) + hash) + value.charCodeAt(i);
    hash |= 0;
  }

  return `fp_${Math.abs(hash).toString(16)}`;
}

function b64ToBytes(b64) {
  return Uint8Array.from(atob(b64), (c) => c.charCodeAt(0));
}

function bytesToB64(bytes) {
  return btoa(String.fromCharCode(...bytes));
}

function concatBytes(...arrays) {
  const total = arrays.reduce((sum, arr) => sum + (arr ? arr.length : 0), 0);
  const out = new Uint8Array(total);
  let offset = 0;
  for (const arr of arrays) {
    if (!arr || arr.length === 0) {
      continue;
    }
    out.set(arr, offset);
    offset += arr.length;
  }
  return out;
}

function withTimeout(promise, timeoutMs, label) {
  return Promise.race([
    promise,
    new Promise((_, reject) => {
      setTimeout(() => reject(new Error(`${label} timeout`)), timeoutMs);
    }),
  ]);
}

function encodeCrockfordBase32(bytes) {
  const alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
  let bits = 0;
  let value = 0;
  let output = '';

  bytes.forEach((byte) => {
    value = (value << 8) | byte;
    bits += 8;

    while (bits >= 5) {
      output += alphabet[(value >>> (bits - 5)) & 31];
      bits -= 5;
    }
  });

  if (bits > 0) {
    output += alphabet[(value << (5 - bits)) & 31];
  }

  return output;
}

function formatRecoveryKey(encodedKey) {
  return String(encodedKey || '').match(/.{1,4}/g)?.join('-') || '';
}

function decodeEnvelope(base64Envelope) {
  const envelope = JSON.parse(atob(base64Envelope));
  const ivB64 = envelope.nonce || envelope.iv;
  const ctB64 = envelope.ciphertext || envelope.ct;
  if (!ivB64 || !ctB64) {
    throw new Error('Invalid envelope');
  }

  return {
    iv: b64ToBytes(ivB64),
    ct: b64ToBytes(ctB64),
  };
}

async function derivePasskeyKEK(credentialId, userId, saltBase64, derivationMode = 'credential-only') {
  const ikmMaterial = (derivationMode === 'credential-user')
    ? encoder.encode(`${credentialId}|${userId || ''}`)
    : encoder.encode(String(credentialId || ''));
  const saltBytes = b64ToBytes(saltBase64);

  const ikm = await crypto.subtle.importKey('raw', ikmMaterial, 'HKDF', false, ['deriveKey']);

  const hkdfParams = {
    name: 'HKDF',
    salt: saltBytes,
    info: encoder.encode('paycal-passkey-kek'),
    hash: 'SHA-256',
  };

  // Some browser/worker combinations can hang on HKDF deriveKey; timeout and fall back.
  try {
    return await withTimeout(
      crypto.subtle.deriveKey(
        hkdfParams,
        ikm,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt']
      ),
      3000,
      'HKDF deriveKey'
    );
  } catch {
    try {
      // Deterministic fallback #1: derive raw bits then import as AES-GCM key.
      const keyBits = await withTimeout(
        crypto.subtle.deriveBits(hkdfParams, ikm, 256),
        3000,
        'HKDF deriveBits'
      );
      return await withTimeout(
        crypto.subtle.importKey('raw', keyBits, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']),
        2000,
        'AES key import'
      );
    } catch {
      throw new Error('Passkey KEK derivation unavailable in this browser context');
    }
  }
}

/**
 * Decode Crockford Base32 to bytes
 * Crockford alphabet: 0-9 A-Z (excludes I, L, O, U)
 * @param {string} str - Crockford Base32 encoded string (with or without dashes)
 * @returns {Uint8Array} Raw bytes
 */
function decodeCrockfordBase32(str) {
  // Crockford Base32 alphabet (no I, L, O, U)
  const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
  const normalized = str
    .replace(/[-\s]/g, '')
    .toUpperCase()
    .replace(/O/g, '0')
    .replace(/[IL]/g, '1');
  
  let bits = 0;
  let value = 0;
  const output = [];
  
  for (const char of normalized) {
    const idx = ALPHABET.indexOf(char);
    if (idx === -1) {
      throw new Error('Invalid character. Note that the recovery key only uses numbers and certain letters (excluding I, L, O, and U).');
    }
    
    value = (value << 5) | idx;
    bits += 5;
    
    if (bits >= 8) {
      output.push((value >> (bits - 8)) & 0xFF);
      bits -= 8;
    }
  }
  
  return new Uint8Array(output);
}

/**
 * Derive recovery KEK from recovery key (Crockford Base32 encoded)
 * @param {string} recoveryKeyEncoded - Recovery key in Crockford Base32 format
 * @param {string} saltBase64 - Account recovery salt (base64)
 * @returns {Promise<CryptoKey>} AES-GCM key for unwrapping DEK
 */
async function deriveRecoveryKEK(recoveryKeyEncoded, saltBase64) {
  // Decode Crockford Base32 to raw bytes
  const recoveryKeyBytes = decodeCrockfordBase32(recoveryKeyEncoded);
  const saltBytes = b64ToBytes(saltBase64);
  
  const ikm = await crypto.subtle.importKey(
    'raw', recoveryKeyBytes, 'HKDF', false, ['deriveKey']
  );
  
  return crypto.subtle.deriveKey(
    {
      name: 'HKDF',
      salt: saltBytes,
      info: encoder.encode('paycal-recovery-kek'),
      hash: 'SHA-256',
    },
    ikm,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt']
  );
}

async function deriveRecoveryKEKFromBytes(recoveryKeyBytes, saltBytes) {
  const ikm = await crypto.subtle.importKey('raw', recoveryKeyBytes, 'HKDF', false, ['deriveKey']);

  return crypto.subtle.deriveKey(
    {
      name: 'HKDF',
      salt: saltBytes,
      info: encoder.encode('paycal-recovery-kek'),
      hash: 'SHA-256',
    },
    ikm,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt']
  );
}

async function generateRecoveryMaterial(payload) {
  if (!self.cryptoState.dek || !self.cryptoState.dekRaw) {
    throw new Error('DEK unavailable');
  }

  const recoveryKeyBytes = crypto.getRandomValues(new Uint8Array(32));
  const saltBytes = crypto.getRandomValues(new Uint8Array(32));
  const recoveryKek = await deriveRecoveryKEKFromBytes(recoveryKeyBytes, saltBytes);
  const wrapIv = crypto.getRandomValues(new Uint8Array(12));
  const wrappedCt = new Uint8Array(await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv: wrapIv },
    recoveryKek,
    self.cryptoState.dekRaw
  ));

  const proofIkm = await crypto.subtle.importKey('raw', recoveryKeyBytes, 'HKDF', false, ['deriveBits']);
  const proofKeyBits = await crypto.subtle.deriveBits({
    name: 'HKDF',
    salt: saltBytes,
    info: encoder.encode('paycal-recovery-proof-v1'),
    hash: 'SHA-256',
  }, proofIkm, 256);

  const encodedRecoveryKey = encodeCrockfordBase32(recoveryKeyBytes);

  return {
    recoveryKey: formatRecoveryKey(encodedRecoveryKey),
    accountRecoverySalt: bytesToB64(saltBytes),
    recoveryProofKey: bytesToB64(new Uint8Array(proofKeyBits)),
    wrappedDekRecovery: btoa(JSON.stringify({
      version: 1,
      nonce: bytesToB64(wrapIv),
      ciphertext: bytesToB64(wrappedCt),
    })),
    dekVersion: Number(payload?.dekVersion || self.cryptoState.dekVersion || 1),
    cryptoVersion: Number(payload?.cryptoVersion || self.cryptoState.cryptoVersion || 1),
  };
}

async function deriveRecoveryProof(payload) {
  const { recoveryKey, accountRecoverySalt, proofNonce, txnId } = payload;

  if (!recoveryKey || !accountRecoverySalt || !proofNonce || !txnId) {
    throw new Error('Missing recovery proof inputs');
  }

  // Generate or retrieve session diagnostic token (not persistent fingerprint)
  if (!self.cryptoState.sessionDiagnosticToken) {
    self.cryptoState.sessionDiagnosticToken = generateSessionDiagnosticToken();
  }
  const sessionToken = self.cryptoState.sessionDiagnosticToken;

  const recoveryKeyBytes = decodeCrockfordBase32(recoveryKey);
  const saltBytes = b64ToBytes(accountRecoverySalt);
  const ikm = await crypto.subtle.importKey('raw', recoveryKeyBytes, 'HKDF', false, ['deriveBits']);
  const proofKeyBits = await crypto.subtle.deriveBits({
    name: 'HKDF',
    salt: saltBytes,
    info: encoder.encode('paycal-recovery-proof-v1'),
    hash: 'SHA-256',
  }, ikm, 256);
  const hmacKey = await crypto.subtle.importKey('raw', new Uint8Array(proofKeyBits), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
  const proof = await crypto.subtle.sign('HMAC', hmacKey, encoder.encode(`paycal-recovery-proof-v1|${txnId}|${proofNonce}|${sessionToken}`));

  return { proof: bytesToB64(new Uint8Array(proof)) };
}

async function unwrapWithPasskeyCredential(payload) {
  const { wrappedDekPasskey, credentialId, userId, saltBase64, dekVersion, cryptoVersion, derivationMode } = payload;

  if (!wrappedDekPasskey || !credentialId || !saltBase64) {
    throw new Error('Missing unwrap inputs');
  }

  const kekPasskey = await derivePasskeyKEK(credentialId, userId, saltBase64, derivationMode || 'credential-only');
  const envelope = decodeEnvelope(wrappedDekPasskey);

  const dekRaw = await crypto.subtle.decrypt(
    { name: 'AES-GCM', iv: envelope.iv },
    kekPasskey,
    envelope.ct
  );

  self.cryptoState.dek = await crypto.subtle.importKey('raw', dekRaw, 'AES-GCM', false, ['encrypt', 'decrypt']);
  self.cryptoState.dekRaw = new Uint8Array(dekRaw);
  self.cryptoState.dekVersion = Number(dekVersion || 1);
  self.cryptoState.cryptoVersion = Number(cryptoVersion || 1);

  return {
    ok: true,
    diagnostics: {
      mode: derivationMode || 'credential-only',
      credentialFp: safeFingerprint(String(credentialId || '')),
      userFp: safeFingerprint(String(userId || '')),
      wrappedLen: String(wrappedDekPasskey || '').length,
    },
  };
}

/**
 * Unwrap DEK with recovery key
 * @param {Object} payload - { wrappedDekRecovery, recoveryKey, accountRecoverySalt, dekVersion, cryptoVersion }
 * @returns {Promise<Object>} { ok: true, diagnostics: {...} }
 */
async function unwrapWithRecoveryKey(payload) {
  const { wrappedDekRecovery, recoveryKey, accountRecoverySalt, dekVersion, cryptoVersion } = payload;

  if (!wrappedDekRecovery || !recoveryKey || !accountRecoverySalt) {
    throw new Error('Missing recovery key unwrap inputs');
  }

  // recoveryKey is the Crockford Base32 encoded string (with or without dashes)
  const kekRecovery = await deriveRecoveryKEK(recoveryKey, accountRecoverySalt);
  const envelope = decodeEnvelope(wrappedDekRecovery);

  let dekRaw;
  try {
    dekRaw = await crypto.subtle.decrypt(
      { name: 'AES-GCM', iv: envelope.iv },
      kekRecovery,
      envelope.ct
    );
  } catch {
    throw new Error('Recovery Key does not match this account. Check the key and try again.');
  }

  self.cryptoState.dek = await crypto.subtle.importKey('raw', dekRaw, 'AES-GCM', false, ['encrypt', 'decrypt']);
  self.cryptoState.dekRaw = new Uint8Array(dekRaw);
  self.cryptoState.dekVersion = Number(dekVersion || 1);
  self.cryptoState.cryptoVersion = Number(cryptoVersion || 1);

  return {
    ok: true,
    diagnostics: {
      mode: 'recovery',
      wrappedLen: String(wrappedDekRecovery || '').length,
      recoveryKeyFp: safeFingerprint(String(recoveryKey || '')),
    },
  };
}

async function wrapCurrentDekWithPasskeyCredential(payload) {
  if (!self.cryptoState.dek) {
    throw new Error('DEK unavailable');
  }

  const { credentialId, userId, saltBase64, derivationMode } = payload;
  const kekPasskey = await derivePasskeyKEK(credentialId, userId, saltBase64, derivationMode || 'credential-only');
  const dekRaw = await crypto.subtle.exportKey('raw', self.cryptoState.dek);
  const wrapIv = crypto.getRandomValues(new Uint8Array(12));
  const wrappedCt = new Uint8Array(await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv: wrapIv },
    kekPasskey,
    dekRaw
  ));

  return {
    wrappedDekPasskey: btoa(JSON.stringify({
      version: 1,
      nonce: bytesToB64(wrapIv),
      ciphertext: bytesToB64(wrappedCt),
    })),
    dekVersion: self.cryptoState.dekVersion,
    cryptoVersion: self.cryptoState.cryptoVersion,
  };
}

async function generateAndWrapWithPasskeyCredential(payload) {
  const { credentialId, userId, saltBase64, dekVersion, cryptoVersion, derivationMode } = payload;
  const kekPasskey = await derivePasskeyKEK(credentialId, userId, saltBase64, derivationMode || 'credential-only');

  const dekRawBytes = crypto.getRandomValues(new Uint8Array(32));
  self.cryptoState.dek = await crypto.subtle.importKey('raw', dekRawBytes, 'AES-GCM', false, ['encrypt', 'decrypt']);
  self.cryptoState.dekRaw = dekRawBytes;
  self.cryptoState.dekVersion = Number(dekVersion || 1);
  self.cryptoState.cryptoVersion = Number(cryptoVersion || 1);

  const wrapIv = crypto.getRandomValues(new Uint8Array(12));
  const wrappedCt = new Uint8Array(await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv: wrapIv },
    kekPasskey,
    dekRawBytes
  ));

  return {
    wrappedDekPasskey: btoa(JSON.stringify({
      version: 1,
      nonce: bytesToB64(wrapIv),
      ciphertext: bytesToB64(wrappedCt),
    })),
    dekVersion: self.cryptoState.dekVersion,
    cryptoVersion: self.cryptoState.cryptoVersion,
  };
}

async function encryptEntry(payload) {
  if (!self.cryptoState.dek) {
    throw new Error('DEK unavailable');
  }

  const entry = payload.entry || {};
  const nonce = crypto.getRandomValues(new Uint8Array(12));
  const aad = String(entry.site_id || '');
  const encoded = encoder.encode(JSON.stringify(entry));
  const ciphertext = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv: nonce, additionalData: encoder.encode(aad) },
    self.cryptoState.dek,
    encoded
  );

  return {
    site_id: entry.site_id,
    // Render snapshot fields are included for deterministic month paint.
    site_name: String(entry.site_name || ''),
    hours: Number(entry.hours || 0),
    regular_hours: Number(entry.regular_hours || 0),
    overtime_hours: Number(entry.overtime_hours || 0),
    living_out_allowance: Number(entry.living_out_allowance || 0),
    travel_hours: Number(entry.travel_hours || 0),
    wage: Number(entry.wage || 0),
    encrypted_blob: btoa(JSON.stringify({
      dek_version: self.cryptoState.dekVersion || 1,
      ciphertext: bytesToB64(new Uint8Array(ciphertext)),
      nonce: bytesToB64(nonce),
      aad,
    })),
  };
}

async function decryptEntry(payload) {
  if (!self.cryptoState.dek) {
    throw new Error('DEK unavailable');
  }

  const entry = payload.entry || {};
  if (!entry.encrypted_blob) {
    return null;
  }

  const blob = JSON.parse(atob(entry.encrypted_blob));
  const entryDekVersion = blob.dek_version || 1;
  const currentDekVersion = self.cryptoState.dekVersion || 1;

  // Future-proofing: detect DEK version mismatch
  if (entryDekVersion !== currentDekVersion) {
    throw new Error(
      `DEK version mismatch: entry uses v${entryDekVersion}, current DEK is v${currentDekVersion}`
    );
  }

  const iv = b64ToBytes(blob.nonce);
  const aad = encoder.encode(blob.aad || '');
  const ciphertext = b64ToBytes(blob.ciphertext);

  const decoded = await crypto.subtle.decrypt(
    { name: 'AES-GCM', iv, additionalData: aad },
    self.cryptoState.dek,
    ciphertext
  );

  return JSON.parse(decoder.decode(decoded));
}

async function decryptEntriesBatch(payload) {
  if (!self.cryptoState.dek) {
    throw new Error('DEK unavailable');
  }

  const entries = payload.entries || [];
  const results = {};
  const failures = [];

  for (const item of entries) {
    const { date, entry } = item;
    
    // Initialize results array for this date if not exists
    if (!results[date]) {
      results[date] = [];
    }

    if (!entry || !entry.encrypted_blob) {
      continue;
    }

    try {
      const decrypted = await decryptEntry({ entry });
      if (decrypted) {
        results[date].push(decrypted);
      }
    } catch {
      // Track failure only once per date
      if (!failures.includes(date)) {
        failures.push(date);
      }
    }
  }

  return { results, failures };
}

async function encryptProfile(payload) {
  if (!self.cryptoState.dek) {
    throw new Error('DEK unavailable');
  }

  const profile = payload.profile || {};
  const nonce = crypto.getRandomValues(new Uint8Array(12));
  // Profile data is sensitive, use random AAD instead of structured data
  const aad = crypto.getRandomValues(new Uint8Array(16));
  const encoded = encoder.encode(JSON.stringify(profile));
  const ciphertext = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv: nonce, additionalData: aad },
    self.cryptoState.dek,
    encoded
  );

  return {
    encrypted_blob: btoa(JSON.stringify({
      dek_version: self.cryptoState.dekVersion || 1,
      ciphertext: bytesToB64(new Uint8Array(ciphertext)),
      nonce: bytesToB64(nonce),
      aad: bytesToB64(aad),
    })),
  };
}

function clear() {
  if (self.cryptoState.dekRaw instanceof Uint8Array) {
    self.cryptoState.dekRaw.fill(0);
  }
  self.cryptoState.dek = null;
  self.cryptoState.dekRaw = null;
  self.cryptoState.dekVersion = 1;
  self.cryptoState.cryptoVersion = 1;
  self.cryptoState.sessionDiagnosticToken = null;  // Clear session token on logout
  return { ok: true };
}

function hasDek() {
  return { hasDek: !!self.cryptoState.dek, dekVersion: self.cryptoState.dekVersion, cryptoVersion: self.cryptoState.cryptoVersion };
}

self.onmessage = async (event) => {
  const { id, action, payload } = event.data || {};

  try {
    let result;
    switch (action) {
      case 'unwrapWithPasskeyCredential':
        result = await unwrapWithPasskeyCredential(payload || {});
        break;
      case 'wrapCurrentDekWithPasskeyCredential':
        result = await wrapCurrentDekWithPasskeyCredential(payload || {});
        break;
      case 'unwrapWithRecoveryKey':
        result = await unwrapWithRecoveryKey(payload || {});
        break;
      case 'deriveRecoveryProof':
        result = await deriveRecoveryProof(payload || {});
        break;
      case 'generateAndWrapWithPasskeyCredential':
        result = await generateAndWrapWithPasskeyCredential(payload || {});
        break;
      case 'generateRecoveryMaterial':
        result = await generateRecoveryMaterial(payload || {});
        break;
      case 'encryptEntry':
        result = await encryptEntry(payload || {});
        break;
      case 'decryptEntry':
        result = await decryptEntry(payload || {});
        break;
      case 'decryptEntriesBatch':
        result = await decryptEntriesBatch(payload || {});
        break;
      case 'encryptProfile':
        result = await encryptProfile(payload || {});
        break;
      case 'clear':
        result = clear();
        break;
      case 'hasDek':
        result = hasDek();
        break;
      default:
        throw new Error('Unknown crypto worker action');
    }

    self.postMessage({ id, ok: true, result });
  } catch (error) {
    const errorMsg = error?.message || 'Crypto worker failure';
    const errorStack = error?.stack || '';
    const diagnostics = {
      action,
      mode: payload?.derivationMode || null,
      hasCredentialId: !!payload?.credentialId,
      credentialFp: safeFingerprint(String(payload?.credentialId || '')),
      hasUserId: !!payload?.userId,
      userFp: safeFingerprint(String(payload?.userId || '')),
      hasSalt: !!payload?.saltBase64,
      saltLen: String(payload?.saltBase64 || '').length,
      wrappedDekLen: String(payload?.wrappedDek || '').length,
      wrappedLen: String(payload?.wrappedDekPasskey || '').length,
    };
    self.postMessage({ 
      id, 
      ok: false, 
      error: errorMsg,
      details: errorStack,
      diagnostics,
    });
  }
};
