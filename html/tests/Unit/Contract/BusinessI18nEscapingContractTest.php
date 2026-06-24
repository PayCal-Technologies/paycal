<?php declare(strict_types=1);

namespace Tests\Unit\Contract;

use PHPUnit\Framework\TestCase;

final class BusinessI18nEscapingContractTest extends TestCase
{
  public function testBusinessI18nHtmlHelperEscapesFallbackValues(): void
  {
    require_once __DIR__ . '/../../../business/_partials/i18n.php';

    $payload = '"><script>alert(1)</script>&';

    $this->assertSame($payload, \PayCal\Domain\businesses_index_i18n($payload));
    $this->assertSame(
      htmlspecialchars($payload, ENT_QUOTES, 'UTF-8'),
      \PayCal\Domain\businesses_index_i18n_html($payload),
    );
  }

  public function testBusinessTemplatesDoNotEchoRawBusinessI18nHelper(): void
  {
    foreach ($this->businessPhpFiles() as $file) {
      $source = file_get_contents($file);
      $this->assertIsString($source);

      $this->assertDoesNotMatchRegularExpression(
        '/<\?php\s+echo\s+businesses_index_i18n\s*\(/',
        $source,
        $file . ' must use businesses_index_i18n_html() for direct HTML output.',
      );
    }
  }

  public function testBusinessI18nHtmlHelperIsOnlyUsedInHtmlTextAndAttributeContexts(): void
  {
    foreach ($this->businessPhpFiles() as $file) {
      $source = file_get_contents($file);
      $this->assertIsString($source);

      $this->assertDoesNotMatchRegularExpression(
        '/\b(?:action|formaction|href|poster|src)\s*=\s*["\'][^"\']*<\?php\s+echo\s+businesses_index_i18n_html\s*\(/i',
        $source,
        $file . ' must use a URL-specific encoder for URL-bearing attributes.',
      );

      $this->assertDoesNotMatchRegularExpression(
        '/\bstyle\s*=\s*["\'][^"\']*<\?php\s+echo\s+businesses_index_i18n_html\s*\(/i',
        $source,
        $file . ' must use a CSS-specific encoder for inline style attributes.',
      );

      $this->assertDoesNotMatchRegularExpression(
        '/<script\b[\s\S]*?businesses_index_i18n_html\s*\([\s\S]*?<\/script>/i',
        $source,
        $file . ' must use JSON or JavaScript-specific encoding inside script blocks.',
      );

      $this->assertDoesNotMatchRegularExpression(
        '/<style\b[\s\S]*?businesses_index_i18n_html\s*\([\s\S]*?<\/style>/i',
        $source,
        $file . ' must use CSS-specific encoding inside style blocks.',
      );
    }
  }

  /**
   * @return array<int, string>
   */
  private function businessPhpFiles(): array
  {
    $root = realpath(__DIR__ . '/../../../business');
    $this->assertIsString($root);

    $files = [];
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
      if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
        continue;
      }

      $files[] = $file->getPathname();
    }

    sort($files);

    return $files;
  }
}
