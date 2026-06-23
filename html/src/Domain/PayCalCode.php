<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Shared PayCal a11y code formatter and checksum helper.
 */
final class PayCalCode
{
  public const ALPHABET = 'ABCDEFGHJKLMNPQRTUWXYZ346789';
  public const RECOVERY_SECRET_LENGTH = 12;
  public const RECOVERY_TOTAL_LENGTH = 14;
  public const EMAIL_SECRET_LENGTH = 4;
  public const EMAIL_TOTAL_LENGTH = 6;

  /**
   * Normalize user-entered PayCal codes for validation and checksum work.
   */
  public static function normalize(string $input): string
  {
    return strtoupper(str_replace([' ', '-'], '', trim($input)));
  }

  /**
   * Return the validation regex for the PayCal code alphabet.
   */
  public static function allowedPattern(): string
  {
    return '/^[' . preg_quote(self::ALPHABET, '/') . ']+$/';
  }

  /**
   * Calculate the two-character checksum for normalized secret material.
   */
  public static function checksum(string $secret): string
  {
    $normalized = self::normalize($secret);
    $hash = 2166136261;
    $length = strlen($normalized);

    for ($i = 0; $i < $length; $i++) {
      $hash ^= ord($normalized[$i]);
      $hash = (($hash * 16777619) & 0xffffffff);
    }

    $space = strlen(self::ALPHABET) * strlen(self::ALPHABET);
    $value = $hash % $space;

    return self::ALPHABET[intdiv($value, strlen(self::ALPHABET))]
      . self::ALPHABET[$value % strlen(self::ALPHABET)];
  }

  /**
   * Append the checksum characters to normalized secret material.
   */
  public static function appendChecksum(string $secret): string
  {
    return self::normalize($secret) . self::checksum($secret);
  }

  /**
   * Generate random secret material using the PayCal code alphabet.
   */
  public static function randomSecret(int $length): string
  {
    $code = '';
    $max = strlen(self::ALPHABET) - 1;
    for ($i = 0; $i < $length; $i++) {
      $code .= self::ALPHABET[random_int(0, $max)];
    }

    return $code;
  }

  /**
   * Generate a formatted account recovery code.
   */
  public static function generateRecoveryCode(): string
  {
    return self::formatRecoveryCode(self::appendChecksum(self::randomSecret(self::RECOVERY_SECRET_LENGTH)));
  }

  /**
   * Generate an email verification code with checksum protection.
   */
  public static function generateEmailVerificationCode(): string
  {
    return self::appendChecksum(self::randomSecret(self::EMAIL_SECRET_LENGTH));
  }

  /**
   * Format normalized recovery code text into readable grouped segments.
   */
  public static function formatRecoveryCode(string $code): string
  {
    $normalized = self::normalize($code);
    if (strlen($normalized) <= 6) {
      return $normalized;
    }

    if (strlen($normalized) <= 12) {
      return substr($normalized, 0, 6) . '-' . substr($normalized, 6);
    }

    return substr($normalized, 0, 6) . '-' . substr($normalized, 6, 6) . '-' . substr($normalized, 12);
  }

  /**
   * Validate code length, alphabet, and checksum for a given secret size.
   */
  public static function validate(string $input, int $secretLength): bool
  {
    $normalized = self::normalize($input);
    if (strlen($normalized) !== $secretLength + 2) {
      return false;
    }
    if (preg_match(self::allowedPattern(), $normalized) !== 1) {
      return false;
    }

    $secret = substr($normalized, 0, $secretLength);
    $checksum = substr($normalized, $secretLength, 2);

    return hash_equals(self::checksum($secret), $checksum);
  }

  /**
   * Extract validated recovery secret material without checksum characters.
   */
  public static function recoverySecretMaterial(string $input): string
  {
    $normalized = self::normalize($input);
    if (!self::validate($normalized, self::RECOVERY_SECRET_LENGTH)) {
      throw new \InvalidArgumentException('Invalid recovery code');
    }

    return substr($normalized, 0, self::RECOVERY_SECRET_LENGTH);
  }
}
