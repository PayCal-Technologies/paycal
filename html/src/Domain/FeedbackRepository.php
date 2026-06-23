<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Redis-backed product feedback persistence for Echo / Signal Panel.
 */
final class FeedbackRepository
{
  public const STATUS_NEW = 'new';
  public const STATUS_TRIAGED = 'triaged';
  public const STATUS_PLANNED = 'planned';
  public const STATUS_INVESTIGATING = 'investigating';
  public const STATUS_RESOLVED = 'resolved';
  public const STATUS_DISMISSED = 'dismissed';
  public const STATUS_DUPLICATE = 'duplicate';

  /** @return array<int, string> */
  public static function statuses(): array
  {
    return [
      self::STATUS_NEW,
      self::STATUS_TRIAGED,
      self::STATUS_PLANNED,
      self::STATUS_INVESTIGATING,
      self::STATUS_RESOLVED,
      self::STATUS_DISMISSED,
      self::STATUS_DUPLICATE,
    ];
  }

  /**
   * @param array<string, string> $fields
   */
  public static function create(array $fields): string
  {
    $feedbackId = self::generateId();
    $key = self::key($feedbackId);
    Database::hset($key, $fields + [
      'feedback_id' => $feedbackId,
      'created_at' => date('c'),
      'status' => self::STATUS_NEW,
      'admin_notes' => '',
      'duplicate_of' => '',
    ]);
    Database::lpush(Keys::FEEDBACK_INDEX, $feedbackId);
    Database::ltrim(Keys::FEEDBACK_INDEX, 0, 4999);

    return $feedbackId;
  }

  /** @return array<string, string> */
  public static function find(string $feedbackId): array
  {
    if (!self::validId($feedbackId)) {
      return [];
    }

    $row = Database::hgetall(self::key($feedbackId));
    $out = [];
    foreach ($row as $key => $value) {
      if (is_string($key)) {
        $out[$key] = $value;
      }
    }

    return $out;
  }

  /**
   * @param array{status?:string,severity?:string,category?:string,page?:string,role?:string,date_range?:string} $filters
   * @return array<int, array<string, string>>
   */
  public static function list(array $filters = [], int $limit = 100): array
  {
    $ids = Database::lrange(Keys::FEEDBACK_INDEX, 0, max(0, min(499, $limit * 5)));
    $rows = [];
    foreach ($ids as $id) {
      $row = self::find($id);
      if ($row === [] || !self::matchesFilters($row, $filters)) {
        continue;
      }

      $rows[] = $row;
      if (count($rows) >= $limit) {
        break;
      }
    }

    return $rows;
  }

  /**
   * Update admin fields.
   */
  public static function updateAdminFields(string $feedbackId, string $status, string $adminNotes, string $duplicateOf = ''): bool
  {
    if (!self::validId($feedbackId) || !in_array($status, self::statuses(), true)) {
      return false;
    }

    if (!Database::exists(self::key($feedbackId))) {
      return false;
    }

    Database::hset(self::key($feedbackId), [
      'status' => $status,
      'admin_notes' => self::clip($adminNotes, 4000),
      'duplicate_of' => self::validId($duplicateOf) ? $duplicateOf : '',
      'updated_at' => date('c'),
      'updated_by' => User::currentUUID(),
    ]);

    return true;
  }

  /**
   * Clip.
   */
  public static function clip(string $value, int $max): string
  {
    $value = trim($value);
    if (function_exists('mb_substr')) {
      return mb_substr($value, 0, $max);
    }

    return substr($value, 0, $max);
  }

  /**
   * Key.
   */
  private static function key(string $feedbackId): string
  {
    return Keys::FEEDBACK . ':' . $feedbackId;
  }

  /**
   * Generate id.
   */
  private static function generateId(): string
  {
    return 'fb_' . bin2hex(random_bytes(12));
  }

  /**
   * Valid id.
   */
  private static function validId(string $feedbackId): bool
  {
    return 1 === preg_match('/^fb_[a-f0-9]{24}$/', $feedbackId);
  }

  /**
   * @param array<string, string> $row
   * @param array<string, string> $filters
   */
  private static function matchesFilters(array $row, array $filters): bool
  {
    foreach (['status', 'severity', 'category', 'user_role'] as $field) {
      $filterKey = $field === 'user_role' ? 'role' : $field;
      $expected = trim((string) ($filters[$filterKey] ?? ''));
      if ($expected !== '' && strcasecmp((string) ($row[$field] ?? ''), $expected) !== 0) {
        return false;
      }
    }

    $page = trim((string) ($filters['page'] ?? ''));
    if ($page !== '' && !str_contains(strtolower((string) ($row['page_path'] ?? '')), strtolower($page))) {
      return false;
    }

    $range = trim((string) ($filters['date_range'] ?? ''));
    if ($range !== '') {
      $days = match ($range) {
        '7' => 7,
        '30' => 30,
        '90' => 90,
        default => 0,
      };
      if ($days > 0) {
        $created = strtotime((string) ($row['created_at'] ?? ''));
        if ($created === false || $created < time() - ($days * 86400)) {
          return false;
        }
      }
    }

    return true;
  }
}
