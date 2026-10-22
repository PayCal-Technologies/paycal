export const PAYCAL_A11Y_ALPHABET = 'ABCDEFGHJKLMNPQRTUWXYZ346789';
export const PAYCAL_RECOVERY_SECRET_LENGTH = 28;
export const PAYCAL_LEGACY_RECOVERY_SECRET_LENGTH = 12;
export const PAYCAL_EMAIL_SECRET_LENGTH = 4;

export function normalizePayCalCode(input) {
  return String(input || '').replace(/[-\s]/g, '').toUpperCase();
}

export function payCalChecksum(secret) {
  const normalized = normalizePayCalCode(secret);
  let hash = 2166136261;
  for (let i = 0; i < normalized.length; i += 1) {
    hash ^= normalized.charCodeAt(i);
    hash = Math.imul(hash, 16777619) >>> 0;
  }
  const space = PAYCAL_A11Y_ALPHABET.length * PAYCAL_A11Y_ALPHABET.length;
  const value = hash % space;
  return PAYCAL_A11Y_ALPHABET[Math.floor(value / PAYCAL_A11Y_ALPHABET.length)]
    + PAYCAL_A11Y_ALPHABET[value % PAYCAL_A11Y_ALPHABET.length];
}

export function hasOnlyPayCalChars(value) {
  return [...normalizePayCalCode(value)].every((char) => PAYCAL_A11Y_ALPHABET.includes(char));
}

export function getPayCalCodeValidationState(value, secretLength) {
  const normalized = normalizePayCalCode(value);
  if (!hasOnlyPayCalChars(normalized)) {
    return 'invalid-char';
  }
  if (normalized.length !== secretLength + 2) {
    return normalized.length === 0 ? 'empty' : 'incomplete';
  }
  return payCalChecksum(normalized.slice(0, secretLength)) === normalized.slice(secretLength)
    ? 'valid'
    : 'checksum';
}

export function validatePayCalCode(value, secretLength) {
  return getPayCalCodeValidationState(value, secretLength) === 'valid';
}

export function formatVerificationCode(value) {
  return normalizePayCalCode(value).slice(0, PAYCAL_EMAIL_SECRET_LENGTH + 2);
}

export function normalizeGroupedCode(value, {
  allowedChars = PAYCAL_A11Y_ALPHABET,
  maxLength = PAYCAL_EMAIL_SECRET_LENGTH + 2,
} = {}) {
  const allowed = String(allowedChars || '').toUpperCase();
  const limit = Number.isFinite(Number(maxLength))
    ? Math.max(0, Number(maxLength))
    : PAYCAL_EMAIL_SECRET_LENGTH + 2;

  return [...String(value ?? '').toUpperCase().replace(/[-\s]/g, '')]
    .filter((char) => allowed === '' || allowed.includes(char))
    .join('')
    .slice(0, limit);
}

export function formatGroupedCode(value, {
  allowedChars = PAYCAL_A11Y_ALPHABET,
  maxLength = PAYCAL_EMAIL_SECRET_LENGTH + 2,
  splitAt = Math.ceil((PAYCAL_EMAIL_SECRET_LENGTH + 2) / 2),
  separator = '-',
} = {}) {
  const normalized = normalizeGroupedCode(value, { allowedChars, maxLength });
  const splitIndex = Number.isFinite(Number(splitAt))
    ? Math.max(1, Number(splitAt))
    : Math.ceil(normalized.length / 2);
  const joiner = String(separator ?? '-');

  if (normalized.length > splitIndex && joiner !== '') {
    return `${normalized.slice(0, splitIndex)}${joiner}${normalized.slice(splitIndex)}`;
  }

  return normalized;
}

export function bindGroupedCodeInput(input, options = {}) {
  if (!input || typeof input.addEventListener !== 'function') {
    return () => '';
  }

  const formatInput = () => {
    input.value = formatGroupedCode(input.value, options);
    return input.value;
  };

  input.addEventListener('input', formatInput);
  input.addEventListener('keyup', formatInput);
  input.addEventListener('blur', formatInput);

  return formatInput;
}

export function formatRecoveryCode(value) {
  const normalized = normalizePayCalCode(value).slice(0, 52);
  const groups = normalized.length > PAYCAL_RECOVERY_SECRET_LENGTH + 2 ? /.{1,4}/g : /.{1,6}/g;
  return normalized.match(groups)?.join('-') || normalized;
}

export function recoverySecretMaterial(recoveryCode) {
  const normalized = normalizePayCalCode(recoveryCode);
  for (const length of [PAYCAL_RECOVERY_SECRET_LENGTH, PAYCAL_LEGACY_RECOVERY_SECRET_LENGTH]) {
    if (validatePayCalCode(normalized, length)) return normalized.slice(0, length);
  }
  throw new Error('Invalid recovery code');
}

export function validateRecoveryCode(value) {
  return validatePayCalCode(value, PAYCAL_RECOVERY_SECRET_LENGTH)
    || validatePayCalCode(value, PAYCAL_LEGACY_RECOVERY_SECRET_LENGTH);
}
