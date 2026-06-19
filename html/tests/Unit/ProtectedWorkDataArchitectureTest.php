<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ProtectedWorkDataArchitectureTest extends TestCase
{
  #[Test]
  public function businessProtectedWorkRowsOnlyUseFetcherThroughKnownGateways(): void
  {
    $root = dirname(__DIR__, 2);
    $srcRoot = $root . '/src';
    $allowedFiles = [
      'Domain/BusinessProtectedDataAccess.php' => true,
    ];
    $violations = [];

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($srcRoot, \FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
      if (!$fileInfo instanceof \SplFileInfo || $fileInfo->getExtension() !== 'php') {
        continue;
      }

      $path = $fileInfo->getPathname();
      $relative = str_replace($srcRoot . '/', '', $path);
      if ($relative === 'Domain/MemberWorkEntriesFetcher.php') {
        continue;
      }

      $source = (string) file_get_contents($path);
      if (!str_contains($source, 'MemberWorkEntriesFetcher::fetchForMembers')) {
        continue;
      }

      if (!isset($allowedFiles[$relative])) {
        $violations[] = $relative;
      }
    }

    $this->assertSame(
      [],
      $violations,
      'Protected business member work rows must originate from BusinessProtectedDataAccess. '
      . 'Any new direct MemberWorkEntriesFetcher::fetchForMembers use needs an explicit architecture review.'
    );
  }

  #[Test]
  public function protectedRowInvariantIsDocumentedAtTheGate(): void
  {
    $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Domain/BusinessProtectedDataAccess.php');

    $this->assertStringContainsString('Protected business member work rows must originate here before any report,', $source);
    $this->assertStringContainsString('export, warmer, or business cache materializes them.', $source);
  }
}
