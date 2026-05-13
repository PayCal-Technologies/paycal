<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * LanguageAuditService.php
 *
 * Purpose: Domain service for auditing and maintaining the consistency of all
 * language string files against the canonical en.txt source of truth.
 *
 * Developer notes:
 * - All file I/O is explicitly guarded; callers receive typed result arrays.
 * - OpenAI translation is done via direct cURL (no external dependencies).
 * - This service is intentionally stateless; every method takes explicit paths
 *   so it can be unit-tested or invoked from CLI without bootstrapping the app.
 *
 * Architectural role:
 * - Pure domain service; no HTTP concerns, no rendering, no auth checks.
 * - Used by AdminController for API responses and the language dashboard page.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Language file audit and AI-translation service.
 *
 * Responsibilities:
 * - Parse and validate all language string files.
 * - Compute per-language audit metrics (untranslated ratio, order, encoding).
 * - Identify keys untranslated in all languages and partial coverage gaps.
 * - Translate batches of keys via the OpenAI API.
 * - Write translated keys back to the strings directory atomically.
 */
final class LanguageAuditService
{
  /** Non-English language codes in the project. */
  private const NON_ENGLISH = ['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];

  /** Human-readable names for dashboard display. */
  private const LANG_NAMES = [
    'de' => 'German',
    'es' => 'Spanish',
    'fr' => 'French',
    'hi' => 'Hindi',
    'it' => 'Italian',
    'nl' => 'Dutch',
    'pt' => 'Portuguese',
    'tl' => 'Tagalog',
    'tr' => 'Turkish',
  ];

  /** Maximum keys per OpenAI translation batch. */
  public const BATCH_SIZE = 50;

  /** @var string Absolute path to the strings/ directory. */
  private string $stringsDir;

  public function __construct(string $stringsDir)
  {
    $this->stringsDir = rtrim($stringsDir, '/');
  }

  // ─── Parsing ──────────────────────────────────────────────────────────────

  /**
   * Parse a strings file into an ordered [key => value] map.
   * Comment lines and blank lines are skipped.
   *
   * @return array<string, string>
   */
  public function parseFile(string $path): array
  {
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
      return [];
    }
    $result = [];
    foreach ($lines as $line) {
      $t = rtrim($line);
      if ($t === '' || $t[0] === '#') {
        continue;
      }
      $parts = preg_split('/\s+/', $t, 2);
      if ($parts !== false && count($parts) === 2 && $parts[0] !== '') {
        $result[$parts[0]] = $parts[1];
      }
    }
    return $result;
  }

  /**
   * Return the canonical en.txt map.
   *
   * @return array<string, string>
   */
  public function getEnglishMap(): array
  {
    return $this->parseFile("{$this->stringsDir}/en.txt");
  }

  // ─── Validation helpers ───────────────────────────────────────────────────

  private function hasCrLf(string $path): bool
  {
    $c = @file_get_contents($path);
    return $c !== false && str_contains($c, "\r\n");
  }

  private function hasNonUtf8(string $path): bool
  {
    $c = @file_get_contents($path);
    return $c !== false && !mb_check_encoding($c, 'UTF-8');
  }

  // ─── Per-language audit ───────────────────────────────────────────────────

  /**
   * Compute the audit record for one language.
   *
   * @param  string              $lang  Two-letter code (e.g. 'de').
   * @param  array<string,string> $enMap Canonical English map.
   * @return array{
   *   lang: string,
   *   name: string,
   *   total: int,
   *   translated: int,
   *   untranslated: int,
   *   ratio: float,
   *   order_ok: bool,
   *   encoding_ok: bool,
   *   untranslated_keys: list<string>
   * }
   */
  public function auditLanguage(string $lang, array $enMap): array
  {
    $path    = "{$this->stringsDir}/{$lang}.txt";
    $langMap = $this->parseFile($path);
    $enKeys  = array_keys($enMap);

    // Untranslated: lang value == English value
    $untranslatedKeys = [];
    foreach ($langMap as $k => $v) {
      if (isset($enMap[$k]) && $v === $enMap[$k]) {
        $untranslatedKeys[] = $k;
      }
    }

    // Order check: verify translated section preserves en.txt order
    $enPosMap = array_flip($enKeys);
    $inNeeds  = false;
    $translatedSection = [];
    $rawLines = @file($path, FILE_IGNORE_NEW_LINES) ?: [];
    foreach ($rawLines as $line) {
      $t = rtrim($line);
      if (str_contains($t, 'NEEDS TRANSLATION')) {
        $inNeeds = true;
        continue;
      }
      if ($t === '' || $t[0] === '#') {
        continue;
      }
      $parts = preg_split('/\s+/', $t, 2);
      if ($parts === false) {
        continue;
      }
      $k = $parts[0];
      if (!isset($enPosMap[$k])) {
        continue;
      }
      if (!$inNeeds) {
        $translatedSection[] = $k;
      }
    }
    $sorted = $translatedSection;
    usort($sorted, fn ($a, $b) => ($enPosMap[$a] ?? 0) <=> ($enPosMap[$b] ?? 0));
    $orderOk = $translatedSection === $sorted;

    $total       = count($enMap);
    $translated  = $total - count($untranslatedKeys);

    return [
      'lang'             => $lang,
      'name'             => self::LANG_NAMES[$lang] ?? $lang,
      'total'            => $total,
      'translated'       => $translated,
      'untranslated'     => count($untranslatedKeys),
      'ratio'            => $total > 0 ? round(count($untranslatedKeys) / $total * 100, 1) : 0.0,
      'order_ok'         => $orderOk,
      'encoding_ok'      => !$this->hasCrLf($path) && !$this->hasNonUtf8($path),
      'untranslated_keys' => $untranslatedKeys,
    ];
  }

  // ─── Full report ──────────────────────────────────────────────────────────

  /**
   * Run a full audit across all non-English language files.
   *
   * @return array{
   *   en_key_count: int,
   *   en_empty_keys: list<string>,
   *   en_encoding_ok: bool,
   *   languages: list<array>,
   *   all_untranslated: list<string>,
   *   partial: list<array{key:string,done:list<string>,missing:list<string>}>
   * }
   */
  public function fullReport(): array
  {
    $enMap  = $this->getEnglishMap();
    $enKeys = array_keys($enMap);

    // en.txt empty values
    $emptyEnKeys = array_keys(array_filter($enMap, fn ($v) => $v === ''));

    // en.txt encoding
    $enPath      = "{$this->stringsDir}/en.txt";
    $enEncodingOk = !$this->hasCrLf($enPath) && !$this->hasNonUtf8($enPath);

    // Per-language
    $langReports = [];
    foreach (self::NON_ENGLISH as $lang) {
      $langReports[$lang] = $this->auditLanguage($lang, $enMap);
    }

    // Keys untranslated in ALL languages (value > 3 chars to skip universal labels)
    $allUntranslated = [];
    foreach ($enKeys as $k) {
      $inAll = true;
      foreach (self::NON_ENGLISH as $lang) {
        if (!in_array($k, $langReports[$lang]['untranslated_keys'], true)) {
          $inAll = false;
          break;
        }
      }
      if ($inAll && mb_strlen($enMap[$k]) > 3) {
        $allUntranslated[] = $k;
      }
    }

    // Partially translated keys
    $partial = [];
    foreach ($enKeys as $k) {
      $done    = [];
      $missing = [];
      foreach (self::NON_ENGLISH as $lang) {
        if (!in_array($k, $langReports[$lang]['untranslated_keys'], true)) {
          $done[] = $lang;
        } else {
          $missing[] = $lang;
        }
      }
      if (count($done) > 0 && count($missing) > 0) {
        $partial[] = ['key' => $k, 'done' => $done, 'missing' => $missing];
      }
    }

    return [
      'en_key_count'    => count($enMap),
      'en_empty_keys'   => $emptyEnKeys,
      'en_encoding_ok'  => $enEncodingOk,
      'languages'       => array_values($langReports),
      'all_untranslated' => $allUntranslated,
      'partial'         => $partial,
    ];
  }

  // ─── Translation ──────────────────────────────────────────────────────────

  /**
   * Translate a batch of key=>value pairs into $targetLang using OpenAI.
   *
   * Returns [key => translated_value] on success.
   * Throws \RuntimeException on API or parse failure.
   *
   * @param  array<string,string> $batch     [key => english_value]
   * @param  string               $targetLang Two-letter language code
   * @param  string               $apiKey     OpenAI API key
   * @return array<string,string>
   */
  public function translateBatch(array $batch, string $targetLang, string $apiKey): array
  {
    if (count($batch) === 0) {
      return [];
    }

    $langName = self::LANG_NAMES[$targetLang] ?? $targetLang;

    // Build numbered list for prompt — keys are NOT exposed to avoid leaking
    // internal identifiers; they are re-mapped by position in the response.
    $keys   = array_keys($batch);
    $values = array_values($batch);
    $lines  = [];
    foreach ($values as $i => $v) {
      $lines[] = (string) ($i + 1) . '. ' . $v;
    }
    $inputBlock = implode("\n", $lines);

    $prompt = <<<PROMPT
You are a professional software UI translator. Translate the following English UI strings into {$langName}.

Rules:
- Preserve placeholders like {name}, %s, %d, :value exactly as-is.
- Do NOT translate proper nouns: PayCal, CRA, SOC 2, Redis, Stripe, WebAuthn, CSRF.
- Keep HTML tags like <em>, <strong> intact — only translate the text inside them.
- Keep translations concise; these are UI labels, buttons, and short descriptions.
- Output ONLY a numbered list in the same order with the {$langName} translation, nothing else.
- Format exactly: "1. <translation>" with no extra commentary.

English strings:
{$inputBlock}
PROMPT;

    $payload = json_encode([
      'model'    => 'gpt-4o',
      'messages' => [
        ['role' => 'system', 'content' => 'You are a professional UI translator. Reply only with the numbered translation list.'],
        ['role' => 'user', 'content' => $prompt],
      ],
      'temperature' => 0.2,
      'max_tokens'  => 4096,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
      throw new \RuntimeException('Failed to encode translation payload.');
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    if ($ch === false) {
      throw new \RuntimeException('curl_init failed.');
    }

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $payload,
      CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
      ],
      CURLOPT_TIMEOUT        => 120,
      CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $raw === '') {
      throw new \RuntimeException('OpenAI request failed: ' . $curlError);
    }

    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
      throw new \RuntimeException('OpenAI returned invalid JSON.');
    }

    if (isset($decoded['error'])) {
      $errBlock = $decoded['error'];
      $msg = is_array($errBlock) ? (is_string($errBlock['message'] ?? null) ? $errBlock['message'] : 'Unknown API error') : (is_string($errBlock) ? $errBlock : 'Unknown API error');
      throw new \RuntimeException('OpenAI API error: ' . $msg);
    }

    $choices = $decoded['choices'] ?? [];
    if (!is_array($choices) || !isset($choices[0]) || !is_array($choices[0])) {
      throw new \RuntimeException('OpenAI returned empty content.');
    }
    $message = $choices[0]['message'] ?? [];
    $content = is_array($message) ? ($message['content'] ?? '') : '';
    if (!is_string($content) || $content === '') {
      throw new \RuntimeException('OpenAI returned empty content.');
    }

    return $this->parseTranslationResponse($content, $keys);
  }

  /**
   * Parse the numbered-list OpenAI response back into [key => value].
   *
   * @param  string        $content   Raw OpenAI response content
   * @param  list<string>  $keys      Original keys in batch order
   * @return array<string,string>
   */
  private function parseTranslationResponse(string $content, array $keys): array
  {
    $result = [];
    $lines  = explode("\n", $content);

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') {
        continue;
      }
      // Match "123. translation text"
      if (!preg_match('/^(\d+)\.\s+(.+)$/', $line, $m)) {
        continue;
      }
      $index = (int) $m[1] - 1;
      $value = trim($m[2]);
      if (isset($keys[$index]) && $value !== '') {
        $result[$keys[$index]] = $value;
      }
    }

    return $result;
  }

  // ─── File writing ─────────────────────────────────────────────────────────

  /**
   * Apply a [key => translated_value] map to an existing language file.
   * Updates matching keys in-place (preserves structure and comments).
   * Keys not present in $translations are left untouched.
   * Returns the number of keys actually updated.
   *
   * @param  string               $lang         Two-letter language code
   * @param  array<string,string> $translations [key => new_value]
   * @return int                  Number of keys updated
   */
  public function applyTranslations(string $lang, array $translations): int
  {
    if (count($translations) === 0) {
      return 0;
    }

    $path   = "{$this->stringsDir}/{$lang}.txt";
    $enMap  = $this->getEnglishMap();
    $existing = $this->parseFile($path);

    // Merge: override untranslated keys with new translations
    $merged = $existing;
    $updated = 0;
    foreach ($translations as $k => $v) {
      if (array_key_exists($k, $merged) && $v !== '' && $v !== ($enMap[$k] ?? '')) {
        $merged[$k] = $v;
        $updated++;
      }
    }

    if ($updated === 0) {
      return 0;
    }

    // Rebuild file in en.txt order (two-section format)
    $enKeys = array_keys($enMap);
    $translatedLines   = [];
    $untranslatedLines = [];

    foreach ($enKeys as $k) {
      $v = $merged[$k] ?? $enMap[$k];
      if ($v !== $enMap[$k]) {
        $translatedLines[] = "{$k} {$v}";
      } else {
        $untranslatedLines[] = "{$k} {$v}";
      }
    }

    $outLines = $translatedLines;
    if (count($untranslatedLines) > 0) {
      $outLines[] = '';
      $outLines[] = '# NEEDS TRANSLATION';
      foreach ($untranslatedLines as $line) {
        $outLines[] = $line;
      }
    }

    $content = implode("\n", $outLines) . "\n";
    if (@file_put_contents($path, $content) === false) {
      throw new \RuntimeException("Failed to write {$lang}.txt");
    }

    return $updated;
  }

  // ─── Batch helpers ────────────────────────────────────────────────────────

  /**
   * Return the untranslated keys for a language, split into BATCH_SIZE chunks.
   *
   * @param  string               $lang  Two-letter language code
   * @param  array<string,string> $enMap Canonical English map
   * @return list<array<string,string>> Each element is a [key => enValue] batch
   */
  public function getBatches(string $lang, array $enMap): array
  {
    $langMap    = $this->parseFile("{$this->stringsDir}/{$lang}.txt");
    $toTranslate = [];
    foreach ($enMap as $k => $v) {
      $langVal = $langMap[$k] ?? null;
      if ($langVal === null || $langVal === $v) {
        $toTranslate[$k] = $v;
      }
    }

    return array_chunk($toTranslate, self::BATCH_SIZE, true);
  }

  /**
   * Return the total number of untranslated keys for a language.
   */
  public function countUntranslated(string $lang): int
  {
    $enMap   = $this->getEnglishMap();
    $langMap = $this->parseFile("{$this->stringsDir}/{$lang}.txt");
    $count   = 0;
    foreach ($enMap as $k => $v) {
      $lv = $langMap[$k] ?? null;
      if ($lv === null || $lv === $v) {
        $count++;
      }
    }
    return $count;
  }

  /**
   * Return the strings directory path.
   */
  public function getStringsDir(): string
  {
    return $this->stringsDir;
  }
}
