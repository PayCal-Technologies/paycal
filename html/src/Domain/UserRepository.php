<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Enums\FormTTL;

/**
 * UserRepository.php
 *
 * Purpose: Primary user persistence gateway for Redis-backed user retrieval,
 * normalization, field mapping, and save/update operations.
 *
 * Developer notes:
 * - This repository is the canonical mapping layer between Redis fields and the
 *   User entity. Avoid ad hoc field-name translations elsewhere.
 * - Changes here ripple into authentication, settings, pay-period generation,
 *   organizations, and recovery flows.
 * - When adding new user fields, update mapping, normalization, and save paths
 *   together so reads and writes stay symmetric.
 * - Controllers should prefer repository helpers over direct user hash access.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * User data repository.
 *
 * Responsibilities:
 * - Rehydrate User entities from persisted storage.
 * - Normalize field values into strongly expected runtime shapes.
 * - Persist user updates with shared field-mapping rules.
 */
final class UserRepository
{

  /**
   * Get user by UUID.
   * Returns null if the user cannot be found.
   */
  public static function getByUUID(?string $userUUID): ?User
  {
    if (null === $userUUID || '' === $userUUID)
      return null;

    // Grab all existing fields from Redis which we will strictly compare to the field map
    $fields = Database::hgetall(Keys::USER . ":{$userUUID}");
    if (empty($fields))
      return null;

    $u = new User();
    $u->user_uuid = $userUUID;
    $fieldMap = self::fieldMap();

    foreach ($fieldMap as $field => $property) {
      if (!array_key_exists($field, $fields))
        continue;

      // Grab redis value since we know it exists and is an acceptable mapped field
      $value = $fields[$field];

      // Special case: enum-typed property
      if ('auth_level' === $property) {
        $u->auth_level = self::normalizeAuthLevel((string) $value);

        continue;
      }

      // Special case: boolean properties from form submissions
      if (str_starts_with($property, 'calendar_work_entry_fields_')) {
        $u->{$property} = (bool) (int) $value;

        continue;
      }

      // Special case: int-typed property
      if ('dek_version' === $property) {
        $u->dek_version = (int) $value;

        continue;
      }

      if ('crypto_version' === $property) {
        $u->crypto_version = (int) $value;

        continue;
      }

      if ('email_verified' === $property) {
        $u->email_verified = (bool) ((int) $value);

        continue;
      }

      if ('recovery_key_generated' === $property) {
        $u->recovery_key_generated = (bool) ((int) $value);

        continue;
      }

      if ('recovery_proof_key_version' === $property) {
        $u->recovery_proof_key_version = (int) $value;

        continue;
      }

      if ('recovery_email_verified' === $property) {
        $u->recovery_email_verified = (bool) ((int) $value);

        continue;
      }

      if ('recovery_email_verify_attempts' === $property) {
        $u->recovery_email_verify_attempts = (int) $value;

        continue;
      }

      // Everything else is currently declared as string on User
      $u->{$property} = (string) $value;
    }

    if (!array_key_exists('variant', $fields) && array_key_exists('theme_mode', $fields)) {
      $legacyVariant = (string) $fields['theme_mode'];
      if ($legacyVariant !== '') {
        $u->variant = $legacyVariant;
      }
    }

    if (isset($fields['auth_level']))
      $u->auth_level = self::normalizeAuthLevel((string) $fields['auth_level']);

    return $u;
  }


      /**
   * Sets user preferences.
   * @param string    $userUUID        User UUID
   * @param string    $passwordHash    Password Hash
       * @param string    $email           Email Address
   * @param AuthLevel $authLevel       User Authorization level (unverified, user, admin, etc...)
   * @param string    $fullName        Full name (John Smith)
   * @param string    $lastSessionHash Last Session hash (TODO: convert to datetime?)
   * @param string    $phone           Phone number
   */
  public static function setUser(
    string $userUUID,
    string $passwordHash,
    string $email,
    AuthLevel $authLevel,
    string $fullName,
    string $lastSessionHash,
    string $phone
  ): void {
    if (empty($userUUID))
      throw new InvalidArgumentException("{$userUUID} cannot be empty.");

    if ($authLevel === AuthLevel::SUPERADMIN) {
      self::demoteOtherSuperAdmins($userUUID);
    }

    $fields = [
      'user_uuid' => $userUUID,
      'password_hash' => $passwordHash,
      'email' => InputSanitizer::sanitizeString($email),
      'auth_level' => $authLevel->value,
      'full_name' => InputSanitizer::sanitizeString($fullName),
      'last_session_hash' => InputSanitizer::sanitizeString($lastSessionHash),
      'phone' => InputSanitizer::sanitizeString($phone),
    ];

    foreach ($fields as $key => $value) {
      Database::hset(Keys::USER . ":{$userUUID}", [$key => $value]);
    }

    self::setUserEmail($userUUID, $email);
  }

  /**
   * Handles demoteOtherSuperAdmins operation.
   */
  private static function demoteOtherSuperAdmins(string $keeperUUID): void
  {
    foreach (Database::scanKeys(Keys::USER . ':*') as $userKey) {
      $candidateUUID = substr($userKey, strlen(Keys::USER . ':'));
      if ($candidateUUID === '' || $candidateUUID === $keeperUUID) {
        continue;
      }

      $currentLevel = (string) Database::hget($userKey, 'auth_level');
      if ($currentLevel !== AuthLevel::SUPERADMIN->value) {
        continue;
      }

      Database::hset($userKey, ['auth_level' => AuthLevel::ADMIN->value]);
    }
  }


  /**
   * @param string $userUUID    User UUID
   * @param string $email       Email Address
   * @param bool   $maintenance Are we in Maintenance Mode?
   */
  public static function setUserEmail(string $userUUID, string $email, bool $maintenance = false): void
  {
    if (empty($userUUID) || empty($email))
      throw new InvalidArgumentException('userUUID or Email cannot be empty.');

    $timestamp = strval(time());
    $sanitizedEmail = InputSanitizer::sanitizeEmail($email);
    $emailKey = Keys::EMAIL . ':' . $sanitizedEmail;
    $legacyEmailKey = Keys::EMAIL . $sanitizedEmail;

    Database::hset($emailKey, ['user_uuid'     => $userUUID]);
    Database::hset($emailKey, ['created'       => $timestamp]);
    Database::hset($emailKey, ['last_modified' => $timestamp]);

    // Legacy key format omitted ':' (e.g. emailuser@example.com). Do not write it.
    Database::unlink($legacyEmailKey);

    if ($maintenance)
      Database::expire($emailKey, FormTTL::ONE_DAY->value);

    if ($maintenance)
      Database::unlink($legacyEmailKey);
  }

    /**
   * Retrieve the user UUID associated with a given email address.
   * Returns an empty string when the email is empty or no mapping exists.
   * @param string $email Raw email address
   * @return string UUID or empty string if not found
   */
  public static function getUUIDFromEmail(string $email): string
  {
    if (empty($email))
      return '';

    $sanitizedEmail = InputSanitizer::sanitizeEmail($email);
    $key = Keys::EMAIL . ":" . $sanitizedEmail;
    $legacyKey = Keys::EMAIL . $sanitizedEmail;
    $field = 'user_uuid';
    $userUUID = (string) Database::hget($key, $field);
    if ($userUUID !== '' && Database::exists(Keys::USER . ':' . $userUUID)) {
      return $userUUID;
    }

    // Self-heal orphaned email index entries from historical delete flows.
    if ($userUUID !== '') {
      Database::unlink($key);
    }

    $legacyUUID = (string) Database::hget($legacyKey, $field);
    if ($legacyUUID !== '' && Database::exists(Keys::USER . ':' . $legacyUUID)) {
      // Self-heal: migrate legacy key to canonical email:<address> shape.
      $timestamp = strval(time());
      Database::hset($key, [
        'user_uuid' => $legacyUUID,
        'last_modified' => $timestamp,
      ]);
      if ((string) Database::hget($key, 'created') === '') {
        Database::hset($key, ['created' => $timestamp]);
      }
      Database::unlink($legacyKey);
      return $legacyUUID;
    }

    if ($legacyUUID !== '') {
      Database::unlink($legacyKey);
    }

    return '';
  }


  /**
   * Check whether an email address is already registered.
   * Sanitizes the input and verifies existence in Redis.
   * @param string $email Raw email address
   * @return bool True if the email key exists, otherwise false
   */
  public static function emailExists(string $email = ''): bool
  {
    if (empty($email))
      return false;

    $sanitizedEmail = InputSanitizer::sanitizeEmail($email);

    $keys = [
      Keys::EMAIL . ':' . $sanitizedEmail,
      Keys::EMAIL . $sanitizedEmail,
    ];

    foreach ($keys as $emailKey) {
      if (!Database::exists($emailKey)) {
        continue;
      }

      $userUUID = (string) Database::hget($emailKey, 'user_uuid');
      if ($userUUID !== '' && Database::exists(Keys::USER . ':' . $userUUID)) {
        if ($emailKey === Keys::EMAIL . $sanitizedEmail) {
          $canonicalKey = Keys::EMAIL . ':' . $sanitizedEmail;
          $timestamp = strval(time());
          Database::hset($canonicalKey, [
            'user_uuid' => $userUUID,
            'last_modified' => $timestamp,
          ]);
          if ((string) Database::hget($canonicalKey, 'created') === '') {
            Database::hset($canonicalKey, ['created' => $timestamp]);
          }
          Database::unlink($emailKey);
        }
        return true;
      }

      // Cleanup stale email-index keys that point to missing users.
      Database::unlink($emailKey);
    }

    return false;
  }


    /**
   * Update last-signin metadata for a user.
   * Stores the sign in timestamp and originating IP address.
   * @param string $userUUID User identifier
   */
  public static function touchLastSignin(string $userUUID): void
  {
    if ('' === $userUUID)
      Log::error('User::touchLastSignin has invalid user UUID');

    $ts  = (string) time();
    $ip  = (string) Security::getClientIPAddress();
    $key = Keys::USER . ":{$userUUID}";

    Database::hset($key, ['last_signin' => $ts]);
    Database::hset($key, ['last_signin_ip' => $ip]);
  }


    /**
   * Build a direct mapping of Redis user field names.
   * The enum values from UserFields are used as both keys and values.
   * Useful for validation, hydration, and whitelist checks.
   * @return \Generator<string,string> Map of redisField => redisField
   */
  public static function fieldMap(): \Generator
  {
    foreach (UserFields::cases() as $case) {
      $redisField = $case->value;
      yield $redisField => $redisField;
    }
  }

  /**
   * Handles find operation.
   */
  public static function find(string $uuid): ?User
  {
    if ('' === $uuid)
      return null;

    $fields = Database::hgetall(Keys::USER . ':' . $uuid);

    if ($fields === [])
      return null;

    return self::hydrate($uuid, $fields);
  }


  /**
   * @param array<string, string> $fields
   */
  private static function hydrate(string $uuid, array $fields): User
  {
    $user = new User();
    $user->user_uuid = $uuid;

    $allowed = array_column(UserFields::cases(), 'value');

    foreach ($allowed as $field) {
      if (!array_key_exists($field, $fields))
        continue;

      $rawValue = $fields[$field];

      if ($field === 'dek_version') {
        $user->dek_version = (int) $rawValue;
        continue;
      }

      if ($field === 'crypto_version') {
        $user->crypto_version = (int) $rawValue;
        continue;
      }

      if ($field === 'recovery_proof_key_version') {
        $user->recovery_proof_key_version = (int) $rawValue;
        continue;
      }

      if ($field === 'email_verified') {
        $user->email_verified = (bool) (int) $rawValue;
        continue;
      }

      if ($field === 'recovery_key_generated') {
        $user->recovery_key_generated = (bool) (int) $rawValue;
        continue;
      }

      if ($field === 'recovery_email_verified') {
        $user->recovery_email_verified = (bool) (int) $rawValue;
        continue;
      }

      if ($field === 'recovery_email_verify_attempts') {
        $user->recovery_email_verify_attempts = (int) $rawValue;
        continue;
      }

      if (str_starts_with($field, 'calendar_work_entry_fields_')) {
        self::assignCalendarField($user, $field, (bool) (int) $rawValue);
        continue;
      }

      // All other user fields are stored as strings in Redis.
      self::assignStringField($user, $field, (string) $rawValue);
    }

    return $user;
  }

  /**
   * Handles assignCalendarField operation.
   */
  private static function assignCalendarField(User $user, string $field, bool $value): void
  {
    switch ($field) {
      case 'calendar_work_entry_fields_hours':
        $user->calendar_work_entry_fields_hours = $value;
        return;
      case 'calendar_work_entry_fields_overtime':
        $user->calendar_work_entry_fields_overtime = $value;
        return;
      case 'calendar_work_entry_fields_living_out':
        $user->calendar_work_entry_fields_living_out = $value;
        return;
      case 'calendar_work_entry_fields_travel':
        $user->calendar_work_entry_fields_travel = $value;
        return;
      default:
        return;
    }
  }

  /**
   * Handles assignStringField operation.
   */
  private static function assignStringField(User $user, string $field, string $value): void
  {
    switch ($field) {
      case 'auth_level':
        $user->auth_level = self::normalizeAuthLevel($value);
        return;
      case 'user_uuid':
        $user->user_uuid = $value;
        return;
      case 'full_name':
        $user->full_name = $value;
        return;
      case 'email':
        $user->email = $value;
        return;
      case 'phone':
        $user->phone = $value;
        return;
      case 'password_hash':
        $user->password_hash = $value;
        return;
      case 'encryption_salt':
        $user->encryption_salt = $value;
        return;
      case 'wrapped_dek':
        $user->wrapped_dek = $value;
        return;
      case 'wrapped_dek_passkey':
        $user->wrapped_dek_passkey = $value;
        return;
      case 'account_recovery_salt':
        $user->account_recovery_salt = $value;
        return;
      case 'wrapped_dek_recovery':
        $user->wrapped_dek_recovery = $value;
        return;
      case 'recovery_proof_key':
        $user->recovery_proof_key = $value;
        return;
      case 'theme':
        $user->theme = $value;
        return;
      case 'variant':
        $user->variant = $value;
        return;
      case 'language':
        $user->language = $value;
        return;
      case 'locale':
        $user->locale = $value;
        return;
      case 'text':
        $user->text = $value;
        return;
      case 'density':
        $user->density = ($value === 'compact') ? 'tight' : $value;
        return;
      case 'dyslexia_typography':
        $user->dyslexia_typography = $value;
        return;
      case 'nav_position_primary':
        $user->nav_position_primary = $value;
        return;
      case 'nav_state_primary':
        $user->nav_state_primary = $value;
        return;
      case 'calendar_autofocus':
        $user->calendar_autofocus = $value;
        return;
      case 'calendar_audio_labels':
        $user->calendar_audio_labels = $value;
        return;
      case 'calendar_day_name_format':
        $user->calendar_day_name_format = $value;
        return;
      case 'calendar_date_label_position':
        $user->calendar_date_label_position = $value;
        return;
      case 'calendar_work_entry_position':
        $user->calendar_work_entry_position = $value;
        return;
      case 'voice':
        $user->voice = $value;
        return;
      case 'audio_feedback':
        $user->audio_feedback = $value;
        return;
      case 'key_uuid':
        $user->key_uuid = $value;
        return;
      case 'pay_frequency':
        $user->pay_frequency = $value;
        return;
      case 'pay_anchor':
        $user->pay_anchor = $value;
        return;
      case 'pay_epoch':
        $user->pay_epoch = $value;
        return;
      case 'pay_period_length':
        $user->pay_period_length = $value;
        return;
      case 'pay_period_start':
        $user->pay_period_start = $value;
        return;
      case 'pay_period_range':
        $user->pay_period_range = $value;
        return;
      case 'default_site_id':
        $user->default_site_id = $value;
        return;
      case 'default_hours':
        $user->default_hours = $value;
        return;
      case 'default_living_out_allowance':
        $user->default_living_out_allowance = $value;
        return;
      case 'default_travel_hours':
        $user->default_travel_hours = $value;
        return;
      case 'province':
        $user->province = $value;
        return;
      case 'timezone':
        $user->timezone = $value;
        return;
      case 'session_timeout':
        $user->session_timeout = $value;
        return;
      case 'emergency_signout_window_ms':
        $user->emergency_signout_window_ms = $value;
        return;
      case 'form_ttl_settings':
        $user->form_ttl_settings = $value;
        return;
      case 'form_ttl_calendar':
        $user->form_ttl_calendar = $value;
        return;
      case 'form_ttl_general':
        $user->form_ttl_general = $value;
        return;
      case 'recovery_email':
        $user->recovery_email = $value;
        return;
      case 'recovery_email_verified_at':
        $user->recovery_email_verified_at = $value;
        return;
      case 'recovery_email_last_sent_at':
        $user->recovery_email_last_sent_at = $value;
        return;
      default:
        return;
    }
  }

  /**
   * Accept both current string values and legacy numeric auth levels.
   */
  private static function normalizeAuthLevel(string $value): AuthLevel
  {
    $normalized = strtolower(trim($value));
    if ($normalized === '') {
      return AuthLevel::GUEST;
    }

    $fromString = AuthLevel::tryFrom($normalized);
    if ($fromString instanceof AuthLevel) {
      return $fromString;
    }

    if (ctype_digit($normalized)) {
      $rank = (int) $normalized;

      if ($rank >= AuthLevel::SUPERADMIN->rank()) return AuthLevel::SUPERADMIN;
      if ($rank >= AuthLevel::ADMIN->rank()) return AuthLevel::ADMIN;
      if ($rank >= AuthLevel::MANAGER->rank()) return AuthLevel::MANAGER;
      if ($rank >= AuthLevel::USER->rank()) return AuthLevel::USER;
      if ($rank >= AuthLevel::VERIFIED->rank()) return AuthLevel::VERIFIED;
      if ($rank >= AuthLevel::UNVERIFIED->rank()) return AuthLevel::UNVERIFIED;
      if ($rank >= AuthLevel::GUEST->rank()) return AuthLevel::GUEST;

      return AuthLevel::PUBLIC;
    }

    return AuthLevel::GUEST;
  }


  /**
   * Handles save operation.
   */
  public static function save(User $user): void
  {
    if ('' === $user->user_uuid) {
      throw new \InvalidArgumentException('User UUID cannot be empty.');
    }

    $key = Keys::USER . ':' . $user->user_uuid;

    $allowed = array_column(UserFields::cases(), 'value');
    $data = [];

    foreach ($allowed as $field) {
      if (!property_exists($user, $field))
        continue;

      $value = $user->{$field};

      if (null === $value)
        continue;

      if (is_bool($value)) {
        $data[$field] = $value ? '1' : '0';
        continue;
      }

      $data[$field] = (string) $value;
    }

    if ($data !== [])
      Database::hset($key, $data);
  }
}


