<?php declare(strict_types=1);

namespace PayCal\Domain;

/** Bounds request work before copying, decoding or sanitizing input. */
final class RequestInputBudget
{
  public const MAX_BYTES = 1048576;
  public const MAX_NODES = 10000;
  public const MAX_DEPTH = 32;

  /** Reject aggregate array growth without serialization or intermediate arrays. */
  public static function assertArray(array $input, int $maxDepth = self::MAX_DEPTH): void
  {
    $nodes = 0;
    $bytes = 0;
    self::walk($input, 0, $maxDepth, $nodes, $bytes);
  }

  /** Share counters across siblings so nesting cannot multiply the budget. */
  private static function walk(array $input, int $depth, int $maxDepth, int &$nodes, int &$bytes): void
  {
    foreach ($input as $key => $value) {
      if (++$nodes > self::MAX_NODES || $depth + 1 > $maxDepth) {
        throw new \RuntimeException('Request input exceeds structural limits.');
      }
      $bytes += strlen((string) $key);
      if (is_array($value)) {
        self::walk($value, $depth + 1, $maxDepth, $nodes, $bytes);
      } elseif (is_scalar($value) || $value === null) {
        $bytes += is_string($value) ? strlen($value) : strlen((string) $value);
      } else {
        throw new \RuntimeException('Request input contains an unsupported value.');
      }
      if ($bytes > self::MAX_BYTES) {
        throw new \RuntimeException('Request input exceeds byte limit.');
      }
    }
  }

  /** Bound JSON complexity before a controller allocates its decoded hash tables.
   * This scanner does not validate or rewrite JSON. Existing parsers own syntax.
   */
  public static function assertBody(string $body): void
  {
    $length = strlen($body);
    if ($length > self::MAX_BYTES) {
      throw new \RuntimeException('Request body exceeds byte limit.');
    }
    $start = strspn($body, " \t\r\n");
    if ($start === $length || ($body[$start] !== '{' && $body[$start] !== '[')) {
      return;
    }
    $depth = 0;
    $tokens = 0;
    $quoted = false;
    for ($i = $start; $i < $length; ++$i) {
      $char = $body[$i];
      if ($quoted) {
        if ($char === '\\') {
          ++$i;
        } elseif ($char === '"') {
          $quoted = false;
        }
        continue;
      }
      if ($char === '"') {
        $quoted = true;
      } elseif ($char === '{' || $char === '[') {
        if (++$depth > self::MAX_DEPTH) {
          throw new \RuntimeException('Request JSON exceeds depth limit.');
        }
      } elseif ($char === '}' || $char === ']') {
        --$depth;
      }
      if ($char === ',' || $char === ':' || $char === '[' || $char === '{') {
        if (++$tokens > self::MAX_NODES) {
          throw new \RuntimeException('Request JSON exceeds structural limit.');
        }
      }
    }
  }

  /** Apply before application initialization, leaving original bytes unchanged. */
  public static function enforce(): void
  {
    try {
      self::assertArray($_GET);
      self::assertArray($_POST);
      self::assertArray($_COOKIE);
      // A length header is not sufficient for chunked or HTTP/2 request bodies.
      $body = file_get_contents('php://input', false, null, 0, self::MAX_BYTES + 1);
      if ($body === false) {
        throw new \RuntimeException('Request body could not be read.');
      }
      self::assertBody($body);
    } catch (\RuntimeException) {
      http_response_code(413);
      header('Content-Type: application/json; charset=utf-8');
      echo '{"status":"error","message":"Request input exceeds supported limits."}';
      exit;
    }
  }
}
