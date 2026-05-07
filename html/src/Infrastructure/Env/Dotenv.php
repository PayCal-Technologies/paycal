<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Env;

/**
 * Dotenv.php
 *
 * Purpose: Minimal .env file loader. Parses KEY=VALUE pairs, skips blank
 * lines and # comments, and populates $_ENV, $_SERVER, and putenv().
 * Replaces vlucas/phpdotenv with zero external dependencies.
 *
 * Developer notes:
 * - createImmutable() will not overwrite keys already present in $_ENV
 *   unless they appear in the $forceKeys list passed to load()/safeLoad().
 * - Values may optionally be wrapped in matching single or double quotes;
 *   the outer quotes are stripped. No variable interpolation is performed.
 * - Inline comments (bare space-hash sequences outside quoted values) are
 *   stripped before the value is stored.
 * - This class intentionally does not implement validation, type casting,
 *   or nested variable expansion — callers own those concerns.
 *
 * Architectural role:
 * - Infrastructure bootstrap service consumed by html/bootstrap/constants.php
 *   and html/config.php before the application layer initialises.
 * - Encapsulates all .env I/O outside the HTTP layer.
 *
 * @category   Configuration
 * @package    PayCal\Infrastructure
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.054.000
 */
final class Dotenv
{
    private function __construct(
        private readonly string $path,
        private readonly bool $immutable,
    ) {}

    /**
     * Create an immutable loader for the given directory.
     * Keys already present in $_ENV will not be overwritten unless listed in
     * the $forceKeys argument of load() or safeLoad().
     */
    public static function createImmutable(string $path): self
    {
        return new self(rtrim($path, '/'), true);
    }

    /**
     * Parse .env from the configured directory.
     * Throws if the file does not exist.
     *
     * @param list<string> $forceKeys Keys to set even if already in $_ENV.
     */
    public function load(array $forceKeys = []): void
    {
        $file = $this->path . '/.env';
        if (!is_file($file)) {
            throw new \RuntimeException("Environment file not found: {$file}");
        }

        $this->parse($file, $forceKeys);
    }

    /**
     * Parse .env from the configured directory.
     * Silently returns if the file does not exist.
     *
     * @param list<string> $forceKeys Keys to set even if already in $_ENV.
     */
    public function safeLoad(array $forceKeys = []): void
    {
        $file = $this->path . '/.env';
        if (!is_file($file)) {
            return;
        }

        $this->parse($file, $forceKeys);
    }

    /** @param list<string> $forceKeys */
    private function parse(string $file, array $forceKeys): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $eqPos = strpos($line, '=');
            if ($eqPos === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $eqPos));
            $value = substr($line, $eqPos + 1);

            // Strip inline comment (space + # outside quoted values).
            $firstChar = $value[0] ?? '';
            if ($firstChar !== '"' && $firstChar !== "'") {
                $hashPos = strpos($value, ' #');
                if ($hashPos !== false) {
                    $value = substr($value, 0, $hashPos);
                }
            }

            $value = $this->stripQuotes(trim($value));

            $force = in_array($key, $forceKeys, true);
            if ($this->immutable && !$force && array_key_exists($key, $_ENV)) {
                continue;
            }

            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    private function stripQuotes(string $value): string
    {
        $len = strlen($value);
        if ($len < 2) {
            return $value;
        }

        $first = $value[0];
        if (($first === '"' || $first === "'") && $value[$len - 1] === $first) {
            return substr($value, 1, $len - 2);
        }

        return $value;
    }
}
