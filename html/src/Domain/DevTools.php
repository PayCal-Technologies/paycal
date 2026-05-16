<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Config\Environment;

/**
 * DevTools.php
 *
 * Purpose: Development-only source inspection utility for rendering highlighted PHP
 *          file contents in admin diagnostic views.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
final class DevTools
{
  /**
   * Displays source code for all PHP files in a directory, prefixed by a heading.
   * Recursively scans from HTML root + subdir and prints highlighted contents.
   *
   * @param string $heading section heading
   * @param string $subdir  subdirectory under HTML to scan
   */
  public static function showSourceCode(string $heading, string $subdir): void
  {
    echo PHP_EOL."<h3>{$heading}</h3>".PHP_EOL;

    $fullPath = realpath(rtrim(Environment::appHome(), '/') . '/html/' . ltrim($subdir, '/'));
    if (false === $fullPath || !is_dir($fullPath)) {
      echo "<p class='devtools-error'>Directory not found: {$subdir}</p>".PHP_EOL;

      return;
    }

    $files = new \RegexIterator(
      new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath)),
      '/\.php$/'
    );

    $paths = [];
    foreach ($files as $file) {
      if (!$file instanceof \SplFileInfo) {
        continue;
      }
      $paths[] = $file->getPathname();
    }

    sort($paths, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($paths as $path) {
      if (!str_contains($path, 'FPDF')) {
        echo PHP_EOL."<h4>{$path}:</h4>".PHP_EOL;
        highlight_file($path);
      }
    }
  }
}
