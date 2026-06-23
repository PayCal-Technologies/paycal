<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\GoldMasterCatalog;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GoldMasterCatalogTest extends TestCase
{
  #[Test]
  public function catalogLoadsDialogGoldenMasterMetadata(): void
  {
    $example = GoldMasterCatalog::find('ui', 'modal-dialog-pattern');

    $this->assertIsArray($example);
    $this->assertSame('modal-dialog-pattern', $example['id']);
    $this->assertSame('Calendar Dialog Pattern', $example['name']);
    $this->assertSame('ui', $example['category']);
    $this->assertSame('Active', $example['status']);
    $this->assertSame('golden_masters/ui/modal-dialog-pattern/README.md', $example['file_path']);
    $this->assertContains('html/sites/_partials/site_editor_dialogs.php', $example['related_production_files']);
    $this->assertContains('html/tests/Integration/BusinessGroupServiceIntegrationTest.php', $example['related_tests']);
  }

  #[Test]
  public function catalogCountsExamplesByCategory(): void
  {
    $categories = GoldMasterCatalog::categories();
    $uiCategory = null;
    foreach ($categories as $category) {
      if ($category['key'] === 'ui') {
        $uiCategory = $category;
      }
    }

    $this->assertIsArray($uiCategory);
    $this->assertSame('UI', $uiCategory['label']);
    $this->assertGreaterThanOrEqual(1, $uiCategory['count']);
  }

  #[Test]
  public function filePreviewOnlyReadsGoldenMasterFiles(): void
  {
    $example = GoldMasterCatalog::find('ui', 'modal-dialog-pattern');
    $this->assertIsArray($example);

    $contents = GoldMasterCatalog::fileContents($example);
    $this->assertStringContainsString('# Calendar Dialog Pattern', $contents);
    $this->assertStringContainsString('Accessibility Contract', $contents);
  }
}
