<?php declare(strict_types=1);

namespace PayCal\Domain\Business;

/**
 * Normalize, validate, and risk-score business display names.
 */
final class BusinessNameGuard
{
  public const DECISION_APPROVED = 'approved';
  public const DECISION_PENDING = 'pending';
  public const DECISION_REJECTED = 'rejected';

  /**
   * @return array{
   *   decision: string,
   *   score: int,
   *   reasons: array<int, string>,
   *   safe_display_name: string,
   *   normalized_name: string,
   *   search_name: string,
   *   name_skeleton: string
   * }
   */
  public static function evaluate(string $rawName): array
  {
    $reasons = [];
    $score = 0;

    $trimmed = trim($rawName);
    if ($trimmed === '') {
      return self::rejected(['empty_name'], 100, '');
    }

    if (preg_match('/<[^>]+>/', $trimmed) === 1 || stripos($trimmed, '<script') !== false) {
      return self::rejected(['html_or_script'], 100, '');
    }

    $normalized = self::normalizeUnicode($trimmed);
    $normalized = self::stripInvisibleControls($normalized);
    $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? '';
    $normalized = trim($normalized);

    if ($normalized === '') {
      return self::rejected(['empty_after_normalization'], 100, '');
    }

    $length = mb_strlen($normalized);
    if ($length < self::nameMinLength() || $length > self::nameMaxLength()) {
      $reasons[] = 'invalid_length';
      $score += 30;
    }

    if (!preg_match('/[\p{L}\p{N}]/u', $normalized)) {
      return self::rejected(['no_alphanumeric'], 100, $normalized);
    }

    if (preg_match('/(https?:\/\/|www\.)/i', $normalized) === 1) {
      $reasons[] = 'url_in_name';
      $score += 40;
    }

    if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $normalized) === 1) {
      $reasons[] = 'email_in_name';
      $score += 40;
    }

    if (preg_match('/\+?\d[\d\s().\-]{7,}\d/', $normalized) === 1) {
      $reasons[] = 'phone_in_name';
      $score += 30;
    }

    if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $normalized) === 1) {
      $reasons[] = 'emoji_in_name';
      $score += 25;
    }

    if (preg_match('/([!?.]){4,}/', $normalized) === 1 || preg_match('/(.)\1{5,}/u', $normalized) === 1) {
      $reasons[] = 'repeated_symbols';
      $score += 10;
    }

    if ($length >= 8 && mb_strtoupper($normalized, 'UTF-8') === $normalized && preg_match('/\p{L}/u', $normalized) === 1) {
      $reasons[] = 'excess_caps';
      $score += 10;
    }

    $searchName = mb_strtolower($normalized, 'UTF-8');
    $skeleton = self::skeleton($searchName);

    if (self::matchesReserved($searchName, $skeleton)) {
      $reasons[] = 'reserved_name';
      $score += 90;
    }

    if (self::hasMixedScript($normalized)) {
      $reasons[] = 'mixed_script';
      $score += 25;
    }

    foreach (self::riskTerms() as $term => $termScore) {
      if ($term !== '' && str_contains($searchName, $term)) {
        $reasons[] = 'risk_term';
        $score += $termScore;
      }
    }

    if (str_contains($searchName, 'paycal') && !self::isExactReservedPaycal($searchName)) {
      $reasons[] = 'platform_impersonation';
      $score += 90;
    }

    $decision = self::decisionForScore($score, $reasons);

    return [
      'decision' => $decision,
      'score' => $score,
      'reasons' => array_values(array_unique($reasons)),
      'safe_display_name' => $normalized,
      'normalized_name' => $normalized,
      'search_name' => $searchName,
      'name_skeleton' => $skeleton,
    ];
  }

  /**
   * @param array<int, string> $reasons
   * @return array{
   *   decision: string,
   *   score: int,
   *   reasons: array<int, string>,
   *   safe_display_name: string,
   *   normalized_name: string,
   *   search_name: string,
   *   name_skeleton: string
   * }
   */
  private static function rejected(array $reasons, int $score, string $displayName): array
  {
    return [
      'decision' => self::DECISION_REJECTED,
      'score' => $score,
      'reasons' => $reasons,
      'safe_display_name' => $displayName,
      'normalized_name' => $displayName,
      'search_name' => mb_strtolower($displayName, 'UTF-8'),
      'name_skeleton' => self::skeleton(mb_strtolower($displayName, 'UTF-8')),
    ];
  }

  /** @param array<int, string> $reasons */
  private static function decisionForScore(int $score, array $reasons): string
  {
    if (in_array('reserved_name', $reasons, true) || in_array('platform_impersonation', $reasons, true)) {
      return self::DECISION_REJECTED;
    }

    if ($score >= 90) {
      return self::DECISION_REJECTED;
    }
    if ($score > self::needsReviewMaxScore()) {
      return self::DECISION_REJECTED;
    }
    if ($score > self::autoApproveMaxScore()) {
      return self::DECISION_PENDING;
    }

    return self::DECISION_APPROVED;
  }

  /**
   * Normalize unicode.
   */
  private static function normalizeUnicode(string $value): string
  {
    if (class_exists(\Normalizer::class)) {
      $normalized = \Normalizer::normalize($value, \Normalizer::FORM_KC);

      return is_string($normalized) ? $normalized : $value;
    }

    return $value;
  }

  /**
   * Strip invisible controls.
   */
  private static function stripInvisibleControls(string $value): string
  {
    return preg_replace('/[\x{00}-\x{1F}\x{7F}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', '', $value) ?? $value;
  }

  /**
   * Skeleton.
   */
  private static function skeleton(string $searchName): string
  {
    $map = [
      '0' => 'o',
      '1' => 'l',
      '3' => 'e',
      '4' => 'a',
      '5' => 's',
      '@' => 'a',
      '$' => 's',
    ];
    $skeleton = strtr($searchName, $map);

    return preg_replace('/[^a-z0-9]+/', '', $skeleton) ?? $skeleton;
  }

  /**
   * Matches reserved.
   */
  private static function matchesReserved(string $searchName, string $skeleton): bool
  {
    foreach (self::reservedNames() as $reserved) {
      $reservedNorm = mb_strtolower(trim($reserved), 'UTF-8');
      if ($reservedNorm === '') {
        continue;
      }
      if ($searchName === $reservedNorm || $skeleton === self::skeleton($reservedNorm)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Is exact reserved paycal.
   */
  private static function isExactReservedPaycal(string $searchName): bool
  {
    return $searchName === 'paycal';
  }

  /**
   * Has mixed script.
   */
  private static function hasMixedScript(string $value): bool
  {
    $hasLatin = preg_match('/\p{Latin}/u', $value) === 1;
    $hasCyrillic = preg_match('/\p{Cyrillic}/u', $value) === 1;
    $hasGreek = preg_match('/\p{Greek}/u', $value) === 1;

    $scriptCount = 0;
    if ($hasLatin) {
      $scriptCount++;
    }
    if ($hasCyrillic) {
      $scriptCount++;
    }
    if ($hasGreek) {
      $scriptCount++;
    }

    return $scriptCount >= 2;
  }

  /** @return array<int, string> */
  private static function reservedNames(): array
  {
    $config = self::config();
    $names = $config['reserved_names'] ?? [];
    if (!is_array($names)) {
      return [];
    }

    $out = [];
    foreach ($names as $name) {
      if (is_scalar($name)) {
        $out[] = (string) $name;
      }
    }

    return $out;
  }

  /** @return array<string, int> */
  private static function riskTerms(): array
  {
    $config = self::config();
    $terms = $config['risk_terms'] ?? [];
    if (!is_array($terms)) {
      return [];
    }

    $out = [];
    foreach ($terms as $term => $termScore) {
      $out[(string) $term] = is_numeric($termScore) ? (int) $termScore : 0;
    }

    return $out;
  }

  /**
   * Name min length.
   */
  private static function nameMinLength(): int
  {
    $config = self::config();
    $name = is_array($config['name'] ?? null) ? $config['name'] : [];

    return is_numeric($name['min_length'] ?? null) ? (int) $name['min_length'] : 2;
  }

  /**
   * Name max length.
   */
  private static function nameMaxLength(): int
  {
    $config = self::config();
    $name = is_array($config['name'] ?? null) ? $config['name'] : [];

    return is_numeric($name['max_length'] ?? null) ? (int) $name['max_length'] : 80;
  }

  /**
   * Auto approve max score.
   */
  private static function autoApproveMaxScore(): int
  {
    $config = self::config();
    $name = is_array($config['name'] ?? null) ? $config['name'] : [];

    return is_numeric($name['auto_approve_max_score'] ?? null) ? (int) $name['auto_approve_max_score'] : 20;
  }

  /**
   * Needs review max score.
   */
  private static function needsReviewMaxScore(): int
  {
    $config = self::config();
    $name = is_array($config['name'] ?? null) ? $config['name'] : [];

    return is_numeric($name['needs_review_max_score'] ?? null) ? (int) $name['needs_review_max_score'] : 59;
  }

  /** @return array<string, mixed> */
  private static function config(): array
  {
    static $config = null;
    if ($config !== null) {
      return $config;
    }

    $path = dirname(__DIR__, 4) . '/config/business-trust.php';
    /** @var array<string, mixed> $loaded */
    $loaded = is_file($path) ? require $path : [];
    $config = $loaded;

    return $config;
  }
}
