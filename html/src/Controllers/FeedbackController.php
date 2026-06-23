<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\FeedbackRepository;
use PayCal\Domain\Response;
use PayCal\Domain\User;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Infrastructure\RateControl\RateLimiter;

final class FeedbackController
{
  private const CATEGORIES = [
    'bug',
    'confusing',
    'missing_feature',
    'ui_layout',
    'accessibility',
    'payroll_calculation',
    'calendar',
    'business',
    'privacy_trust',
    'performance',
    'content_copy',
    'praise',
  ];

  private const SEVERITIES = ['low', 'medium', 'high', 'blocking'];

  private const PAIN_POINTS = [
    'got_stuck',
    'expected_different',
    'looks_wrong',
    'dont_trust_result',
    'too_slow',
    'could_not_find',
    'need_explained',
    'need_on_mobile',
  ];

  /**
   * Handle feedback submission.
   */
  #[Route('feedback', ['POST'])]
  public function submit(): void
  {
    if (!Authentication::validateAndTouchSession()) {
      Response::error('Authentication required.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $userUUID = User::currentUUID();
    if ($userUUID === '') {
      Response::error('Missing authenticated user context.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $rate = RateLimiter::checkFeedbackLimit($userUUID);
    if (!$rate['allowed']) {
      Response::error('Feedback rate limit exceeded. Please try again shortly.', ['remaining' => $rate['remaining']], HttpStatus::HTTP_TOO_MANY_REQUESTS);
      return;
    }

    $input = self::readJson();
    if ($input === null) {
      Response::error('Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $topic = FeedbackRepository::clip(self::scalar($input['topic'] ?? ''), 120);
    $notes = FeedbackRepository::clip(self::scalar($input['notes'] ?? ''), 4000);
    $category = self::slug(self::scalar($input['category'] ?? ''));
    $severity = self::slug(self::scalar($input['severity'] ?? ''));

    if ($topic === '' || $notes === '') {
      Response::error('Topic and notes are required.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }
    if (!in_array($category, self::CATEGORIES, true)) {
      Response::error('Invalid feedback category.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }
    if (!in_array($severity, self::SEVERITIES, true)) {
      Response::error('Invalid feedback severity.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $context = self::normalizeContext($input['context'] ?? []);
    $diagnostics = self::normalizeDiagnostics($input['diagnostics'] ?? []);
    $tags = self::normalizeTags($input['tags'] ?? []);
    $painPoints = self::normalizePainPoints($input['pain_points'] ?? []);

    $pagePath = self::safePagePath(self::scalar($input['page_path'] ?? ($context['page_path'] ?? '')));
    $pageTitle = FeedbackRepository::clip(self::scalar($input['page_title'] ?? ($context['page_title'] ?? '')), 180);

    $feedbackId = FeedbackRepository::create([
      'user_uuid' => $userUUID,
      'topic' => self::sanitizeText($topic),
      'notes' => self::sanitizeText($notes),
      'category' => $category,
      'tags_json' => self::encodeJson($tags),
      'pain_points_json' => self::encodeJson($painPoints),
      'severity' => $severity,
      'page_path' => $pagePath,
      'page_title' => self::sanitizeText($pageTitle),
      'context_json' => self::encodeJson($context),
      'diagnostics_json' => self::encodeJson($diagnostics),
      'user_role' => self::sanitizeText(self::scalar($context['user_role'] ?? User::current()->auth_level->value ?? '')),
      'business_id' => self::safeIdentifier(self::scalar($context['business_id'] ?? '')),
      'site_id' => self::safeIdentifier(self::scalar($context['site_id'] ?? '')),
    ]);

    Response::success('Feedback submitted.', ['feedback_id' => $feedbackId]);
  }

  /** @return null|array<string, mixed> */
  private static function readJson(): ?array
  {
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 60000) {
      return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return null;
    }

    $input = [];
    foreach ($decoded as $key => $value) {
      if (!is_string($key)) {
        return null;
      }
      $input[$key] = $value;
    }

    return $input;
  }

  /**
   * Read a scalar value as trimmed text.
   */
  private static function scalar(mixed $value): string
  {
    return is_scalar($value) ? trim((string) $value) : '';
  }

  /**
   * Normalize text for slug-safe storage.
   */
  private static function slug(string $value): string
  {
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
    return trim($slug, '_');
  }

  /**
   * Sanitize free-form feedback text.
   */
  private static function sanitizeText(string $value): string
  {
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    return trim($value);
  }

  /** @return array<int, string> */
  private static function normalizeTags(mixed $raw): array
  {
    if (!is_array($raw)) {
      return [];
    }

    $tags = [];
    foreach ($raw as $value) {
      $tag = self::slug(self::scalar($value));
      if ($tag !== '' && strlen($tag) <= 40) {
        $tags[$tag] = $tag;
      }
    }

    return array_slice(array_values($tags), 0, 12);
  }

  /** @return array<int, string> */
  private static function normalizePainPoints(mixed $raw): array
  {
    if (!is_array($raw)) {
      return [];
    }

    $points = [];
    foreach ($raw as $value) {
      $point = self::slug(self::scalar($value));
      if (in_array($point, self::PAIN_POINTS, true)) {
        $points[$point] = $point;
      }
    }

    return array_values($points);
  }

  /** @return array<string, mixed> */
  private static function normalizeContext(mixed $raw): array
  {
    if (!is_array($raw)) {
      return [];
    }

    $allowed = ['page_path', 'page_title', 'section', 'viewport', 'browser', 'platform', 'language', 'theme', 'text_preferences', 'user_role', 'business_id', 'site_id', 'team_id', 'feature_flags'];
    return self::pickAllowed($raw, $allowed, 12000);
  }

  /** @return array<string, mixed> */
  private static function normalizeDiagnostics(mixed $raw): array
  {
    if (!is_array($raw)) {
      return [];
    }

    $allowed = ['client_errors', 'api_statuses', 'performance', 'phantomwing_summary'];
    return self::pickAllowed($raw, $allowed, 18000);
  }

  /**
   * @param array<mixed> $raw
   * @param array<int, string> $allowed
   * @return array<string, mixed>
   */
  private static function pickAllowed(array $raw, array $allowed, int $maxEncodedBytes): array
  {
    $out = [];
    foreach ($allowed as $key) {
      if (array_key_exists($key, $raw)) {
        $out[$key] = self::sanitizeValue($raw[$key], 0);
      }
    }

    while (strlen(self::encodeJson($out)) > $maxEncodedBytes && $out !== []) {
      array_pop($out);
    }

    return $out;
  }

  /**
   * Sanitize a nested feedback payload value.
   */
  private static function sanitizeValue(mixed $value, int $depth): mixed
  {
    if ($depth > 3) {
      return null;
    }
    if (is_scalar($value)) {
      return FeedbackRepository::clip(self::sanitizeText((string) $value), 600);
    }
    if (!is_array($value)) {
      return null;
    }

    $out = [];
    $count = 0;
    foreach ($value as $key => $item) {
      if ($count >= 40) {
        break;
      }
      if (is_string($key) && self::blockedKey($key)) {
        continue;
      }
      $out[is_string($key) ? self::slug($key) : $count] = self::sanitizeValue($item, $depth + 1);
      $count++;
    }

    return $out;
  }

  /**
   * Return whether a feedback payload key is blocked.
   */
  private static function blockedKey(string $key): bool
  {
    return 1 === preg_match('/(email|name|phone|address|amount|hours|rate|pay|salary|wage|token|secret|encrypted|blob|payload|body|form|value)/i', $key);
  }

  /**
   * Normalize a submitted page path for storage.
   */
  private static function safePagePath(string $value): string
  {
    $path = parse_url($value, PHP_URL_PATH);
    $path = is_string($path) && $path !== '' ? $path : '/';
    return FeedbackRepository::clip($path, 240);
  }

  /**
   * Normalize a submitted browser or session identifier.
   */
  private static function safeIdentifier(string $value): string
  {
    $value = trim($value);
    if ($value === '') {
      return '';
    }

    return substr(hash('sha256', $value), 0, 24);
  }

  /** @param mixed $value */
  private static function encodeJson($value): string
  {
    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
  }
}
