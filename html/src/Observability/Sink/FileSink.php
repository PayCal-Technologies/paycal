<?php declare(strict_types=1);

namespace PayCal\Observability\Sink;

use PayCal\Domain\Config\Environment;
use PayCal\Observability\DiagnosticEvent;
use PayCal\Observability\TraceGateDecision;
use PayCal\Observability\TraceGatePolicy;

/**
 * Dev FileSink — append-only JSONL for mac/dev environments only.
 *
 * Path: {appHome}/logs/diagnostic.log
 * Never writes in production even if misconfigured — guarded at write time.
 */
final class FileSink implements DiagnosticSinkInterface
{
  private const LOG_FILENAME = 'diagnostic.log';

  /**
   * Return the registry identifier for the file sink.
   */
  public function id(): string
  {
    return 'file';
  }

  /**
   * Append an accepted diagnostic event to the dev log file.
   */
  public function write(DiagnosticEvent $event, TraceGateDecision $decision): void
  {
    if (!TraceGatePolicy::isDevEnvironment()) {
      return;
    }

    $path = self::logPath();
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0o755, true) && !is_dir($dir)) {
      return;
    }
    if (!is_writable($dir)) {
      return;
    }

    $line = json_encode([
      'sink' => $this->id(),
      'reason' => $decision->reason,
      'event' => $event->toArray(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($line === false) {
      return;
    }

    @file_put_contents($path, $line . PHP_EOL, FILE_APPEND);
  }

  /**
   * Return the diagnostic log file path.
   */
  public static function logPath(): string
  {
    return rtrim(Environment::appHome(), '/') . '/logs/' . self::LOG_FILENAME;
  }
}
