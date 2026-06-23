<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

/**
 * File-backed catalog for PayCal golden master examples.
 *
 * The catalog is intentionally read-only. It loads static metadata from the
 * repository's golden_masters directory and only previews files inside that
 * directory.
 *
 * @phpstan-type GoldMasterExample array{
 *   id:string,
 *   name:string,
 *   category:string,
 *   category_label:string,
 *   file_path:string,
 *   source_path:string,
 *   metadata_path:string,
 *   purpose:string,
 *   pattern_type:string,
 *   status:string,
 *   status_key:string,
 *   last_reviewed:string,
 *   owner:string,
 *   notes:string,
 *   use_when:array<int, string>,
 *   do_not_use_when:array<int, string>,
 *   related_production_files:array<int, string>,
 *   related_tests:array<int, string>
 * }
 */
final class GoldMasterCatalog
{
  /** @var array<string, array{label:string, description:string}> */
  private const CATEGORIES = [
    'php' => [
      'label' => 'PHP',
      'description' => 'Controllers, API endpoints, domain services, and Redis repositories.',
    ],
    'js' => [
      'label' => 'JavaScript',
      'description' => 'Modules, keyboard behavior, dialog binding, and browser interactions.',
    ],
    'css' => [
      'label' => 'CSS',
      'description' => 'Tokens, layout density, responsive rules, and visual contracts.',
    ],
    'ui' => [
      'label' => 'UI',
      'description' => 'Dialogs, DataGrid surfaces, admin panels, and settings cards.',
    ],
    'tests' => [
      'label' => 'Tests',
      'description' => 'Unit, integration, accessibility, and regression examples.',
    ],
  ];

  /** @var array<int, string> */
  private const STATUSES = [
    'Draft',
    'Active',
    'Needs Review',
    'Deprecated',
    'Replaced',
  ];

  /** @return array<int, array{key:string, label:string, description:string, count:int}> */
  public static function categories(): array
  {
    $counts = [];
    foreach (array_keys(self::CATEGORIES) as $key) {
      $counts[$key] = 0;
    }

    foreach (self::examples() as $example) {
      $category = $example['category'];
      if (isset($counts[$category])) {
        $counts[$category]++;
      }
    }

    $categories = [];
    foreach (self::CATEGORIES as $key => $details) {
      $categories[] = [
        'key' => $key,
        'label' => $details['label'],
        'description' => $details['description'],
        'count' => $counts[$key],
      ];
    }

    return $categories;
  }

  /**
   * @phpstan-return list<GoldMasterExample>
   */
  public static function examples(): array
  {
    $examples = [];
    foreach (array_keys(self::CATEGORIES) as $category) {
      $pattern = self::rootPath() . '/' . $category . '/*/metadata.json';
      $metadataFiles = glob($pattern);
      if (!is_array($metadataFiles)) {
        continue;
      }

      sort($metadataFiles, SORT_STRING);
      foreach ($metadataFiles as $metadataFile) {
        $example = self::loadMetadataFile($metadataFile);
        if ($example === null) {
          continue;
        }

        $examples[] = $example;
      }
    }

    usort($examples, static function (array $left, array $right): int {
      $categoryCompare = strcmp($left['category'], $right['category']);
      if ($categoryCompare !== 0) {
        return $categoryCompare;
      }

      return strcmp($left['name'], $right['name']);
    });

    return $examples;
  }

  /**
   * @phpstan-return array<string, list<GoldMasterExample>>
   */
  public static function groupedExamples(): array
  {
    $grouped = [];
    foreach (array_keys(self::CATEGORIES) as $category) {
      $grouped[$category] = [];
    }

    foreach (self::examples() as $example) {
      $grouped[$example['category']][] = $example;
    }

    return $grouped;
  }

  /** @phpstan-return GoldMasterExample|null */
  public static function defaultExample(): ?array
  {
    $examples = self::examples();
    return $examples[0] ?? null;
  }

  /** @phpstan-return GoldMasterExample|null */
  public static function find(string $category, string $id): ?array
  {
    $category = self::cleanKey($category);
    $id = self::cleanKey($id);
    if ($category === '' || $id === '') {
      return self::defaultExample();
    }

    foreach (self::examples() as $example) {
      if ($example['category'] === $category && $example['id'] === $id) {
        return $example;
      }
    }

    return self::defaultExample();
  }

  /** @param array<string, mixed> $example */
  public static function fileContents(array $example): string
  {
    $sourcePath = is_scalar($example['source_path'] ?? null) ? (string) $example['source_path'] : '';
    $path = self::safeAbsolutePath($sourcePath);
    if ($path === null || !is_file($path)) {
      return '';
    }

    $contents = file_get_contents($path);
    return is_string($contents) ? $contents : '';
  }

  /**
   * Resolve the repository-local golden_masters root directory.
   */
  public static function rootPath(): string
  {
    $appHome = Environment::appHome();
    if ($appHome === '') {
      $appHome = dirname(__DIR__, 3) . '/';
    }

    return rtrim($appHome, '/') . '/golden_masters';
  }

  /** @phpstan-return GoldMasterExample|null */
  private static function loadMetadataFile(string $metadataFile): ?array
  {
    $contents = file_get_contents($metadataFile);
    if (!is_string($contents) || trim($contents) === '') {
      return null;
    }

    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
      return null;
    }

    $metadata = [];
    foreach ($decoded as $key => $value) {
      if (is_string($key)) {
        $metadata[$key] = $value;
      }
    }

    return self::normalizeMetadata($metadata, $metadataFile);
  }

  /**
   * @param array<string, mixed> $metadata
   * @phpstan-return GoldMasterExample|null
   */
  private static function normalizeMetadata(array $metadata, string $metadataFile): ?array
  {
    $id = self::cleanKey(self::stringValue($metadata['id'] ?? ''));
    $name = self::stringValue($metadata['name'] ?? '');
    $category = self::cleanKey(self::stringValue($metadata['category'] ?? ''));
    $sourcePath = self::normalizeSourcePath(self::stringValue($metadata['file_path'] ?? ''));
    $status = self::stringValue($metadata['status'] ?? 'Draft');

    if ($id === '' || $name === '' || !isset(self::CATEGORIES[$category]) || $sourcePath === '') {
      return null;
    }

    if (!in_array($status, self::STATUSES, true)) {
      $status = 'Needs Review';
    }

    return [
      'id' => $id,
      'name' => $name,
      'category' => $category,
      'category_label' => self::CATEGORIES[$category]['label'],
      'file_path' => 'golden_masters/' . $sourcePath,
      'source_path' => $sourcePath,
      'metadata_path' => self::displayPath($metadataFile),
      'purpose' => self::stringValue($metadata['purpose'] ?? ''),
      'pattern_type' => self::stringValue($metadata['pattern_type'] ?? ''),
      'status' => $status,
      'status_key' => strtolower(str_replace(' ', '-', $status)),
      'last_reviewed' => self::stringValue($metadata['last_reviewed'] ?? ''),
      'owner' => self::stringValue($metadata['owner'] ?? ''),
      'notes' => self::stringValue($metadata['notes'] ?? ''),
      'use_when' => self::stringList($metadata['use_when'] ?? []),
      'do_not_use_when' => self::stringList($metadata['do_not_use_when'] ?? []),
      'related_production_files' => self::stringList($metadata['related_production_files'] ?? []),
      'related_tests' => self::stringList($metadata['related_tests'] ?? []),
    ];
  }

  /**
   * Normalize a catalog source path so it stays relative to golden_masters.
   */
  private static function normalizeSourcePath(string $path): string
  {
    $path = trim(str_replace('\\', '/', $path));
    if (str_starts_with($path, 'golden_masters/')) {
      $path = substr($path, strlen('golden_masters/'));
    }

    $path = ltrim($path, '/');
    if ($path === '' || str_contains($path, '..')) {
      return '';
    }

    return $path;
  }

  /**
   * Resolve a catalog source path and reject paths outside golden_masters.
   */
  private static function safeAbsolutePath(string $sourcePath): ?string
  {
    $sourcePath = self::normalizeSourcePath($sourcePath);
    if ($sourcePath === '') {
      return null;
    }

    $root = realpath(self::rootPath());
    $candidate = realpath(self::rootPath() . '/' . $sourcePath);
    if ($root === false || $candidate === false) {
      return null;
    }

    $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if ($candidate !== $root && !str_starts_with($candidate, $rootPrefix)) {
      return null;
    }

    return $candidate;
  }

  /**
   * Convert an absolute path to a repository-relative display path when possible.
   */
  private static function displayPath(string $absolutePath): string
  {
    $appHome = realpath(Environment::appHome());
    if ($appHome !== false && str_starts_with($absolutePath, $appHome . DIRECTORY_SEPARATOR)) {
      return substr($absolutePath, strlen($appHome) + 1);
    }

    return $absolutePath;
  }

  /**
   * Normalize free-form keys for category and example lookup.
   */
  private static function cleanKey(string $value): string
  {
    $value = strtolower(trim($value));
    $cleaned = preg_replace('/[^a-z0-9_-]+/', '-', $value);
    return is_string($cleaned) ? trim($cleaned, '-') : '';
  }

  /**
   * Return trimmed scalar metadata values and reject compound values.
   */
  private static function stringValue(mixed $value): string
  {
    return is_scalar($value) ? trim((string) $value) : '';
  }

  /** @return array<int, string> */
  private static function stringList(mixed $value): array
  {
    if (!is_array($value)) {
      return [];
    }

    $list = [];
    foreach ($value as $item) {
      if (!is_scalar($item)) {
        continue;
      }

      $text = trim((string) $item);
      if ($text !== '') {
        $list[] = $text;
      }
    }

    return $list;
  }
}
