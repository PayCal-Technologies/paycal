<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * AriaEcho
 *
 * Echo is the guardian who repeats digital content across assistive pathways
 * and devices, while Cadence shapes the pacing so repeated content is clear.
 *
 * Codename: Echo (Greek mythology). Echo was the nymph associated with
 * repeated voice and audible phrasing; this utility applies that concept to
 * accessibility narration by shaping pauses, rhythm, and timing.
 *
 * Tagline: "She echoes every word, then gives it room to breathe."
 */
class AriaEcho
{
  /**
   * Handles normalizeText operation.
   */
  private static function normalizeText(string $text): string
  {
    $normalized = trim($text);
    if ('' === $normalized) {
      return '';
    }

    // Normalize slash-separated chunks into comma pauses for narration.
    $normalized = preg_replace('/\s*\/\s*/u', ', ', $normalized) ?? $normalized;

    // Normalize whitespace around punctuation used for pauses.
    $normalized = preg_replace('/\s*,\s*/u', ', ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\s*;\s*/u', '; ', $normalized) ?? $normalized;
    $normalized = preg_replace('/\s*\.\s*/u', '. ', $normalized) ?? $normalized;

    // Collapse duplicate whitespace and trim trailing spacing.
    $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

    return trim($normalized);
  }

  /**
   * @param array<int, string> $parts
   */
  private static function joinParts(array $parts, string $delimiter): string
  {
    $clean = [];
    foreach ($parts as $part) {
      $value = self::normalizeText((string) $part);
      if ('' !== $value) {
        $clean[] = $value;
      }
    }

    if ([] === $clean) {
      return '';
    }

    if (1 === count($clean)) {
      return $clean[0];
    }

    $last = array_pop($clean);
    $sep = '' === trim($delimiter) ? ', ' : $delimiter;

    return implode($sep, $clean).$sep.'and '.$last;
  }

  /**
   * Cadence power: normalize punctuation and spacing to improve spoken flow
   * across screen readers, speech synthesis, and braille-oriented narration.
   *
   * Accepts either a single string or an array of phrase fragments.
   * When an array is provided, fragments are joined with the configured
   * delimiter plus a final "and" for natural cadence.
   *
   * @param array<int, string>|string $text
   */
  public static function cadence(array|string $text, string $delimiter = ', '): string
  {
    if (is_array($text)) {
      return self::joinParts($text, $delimiter);
    }

    $normalized = self::normalizeText($text);
    if ('' === $normalized) {
      return '';
    }

    // Optional parsing path for raw single-string lists.
    $parts = [];
    if ('' !== trim($delimiter) && str_contains($normalized, $delimiter)) {
      $parts = explode($delimiter, $normalized);
    } elseif (preg_match('/[\|\/;]/u', $normalized) === 1) {
      $parts = preg_split('/\s*(?:\||\/|;)\s*/u', $normalized) ?: [];
    }

    if (count($parts) > 1) {
      return self::joinParts($parts, $delimiter);
    }

    return $normalized;
  }

  /**
   * Backward-compatible alias for list cadence formatting.
   *
   * @param array<int, string> $parts
   */
  public static function cadenceList(array $parts): string
  {
    return self::cadence($parts, ', ');
  }
}

