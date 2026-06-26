<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class DataGridKeyboardFocusContractTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  #[Test]
  public function datagridKeyboardNavigationScrollsFocusedRowIntoView(): void
  {
    $datagridJs = (string) file_get_contents($this->projectRoot() . '/html/js/datagrid/index.php');

    $this->assertStringContainsString('function scrollDatagridRowIntoView(row)', $datagridJs);

    $scrollStart = strpos($datagridJs, 'function scrollDatagridRowIntoView(row)');
    $this->assertNotFalse($scrollStart);
    $scrollBody = substr($datagridJs, $scrollStart, 450);

    $this->assertStringContainsString('scrollIntoView({ block: \'nearest\', inline: \'nearest\'', $scrollBody);
    $this->assertStringContainsString("behavior: 'auto'", $scrollBody);

    $setActiveStart = strpos($datagridJs, 'const setActiveRow = (row, options = {}) =>');
    $this->assertNotFalse($setActiveStart);
    $setActiveBody = substr($datagridJs, $setActiveStart, 900);

    $this->assertStringContainsString('preventScroll: true', $setActiveBody);
    $this->assertStringContainsString('scrollDatagridRowIntoView(row)', $setActiveBody);
    $this->assertStringContainsString('options.scroll === false', $setActiveBody);
    $this->assertStringContainsString('options.preventScroll === true', $setActiveBody);
  }

  #[Test]
  public function sitesListUsesSharedDatagridKeyboardNavigation(): void
  {
    $sitesJs = (string) file_get_contents($this->projectRoot() . '/html/js/sites/index.php');

    $this->assertStringContainsString('bindDataGridKeyboardNavigation', $sitesJs);
    $this->assertStringContainsString("root: 'sites_list_panel'", $sitesJs);
  }

  #[Test]
  public function businessSitesAndGroupsUseSharedDatagridKeyboardNavigation(): void
  {
    $sitesJs = (string) file_get_contents($this->projectRoot() . '/html/js/business/subpages/sites.js.php');
    $groupsJs = (string) file_get_contents($this->projectRoot() . '/html/js/business/subpages/groups.js.php');

    $this->assertStringContainsString('bindDataGridKeyboardNavigation', $sitesJs);
    $this->assertStringContainsString('bindDataGridKeyboardNavigation', $groupsJs);
  }

  #[Test]
  public function businessMembersGridRowFocusScrollsIntoViewWhenNotSuppressed(): void
  {
    $membersJs = (string) file_get_contents($this->projectRoot() . '/html/js/business/subpages/members.js.php');

    $focusStart = strpos($membersJs, 'const focusMembersGridRow = (row, options = {}) =>');
    $this->assertNotFalse($focusStart);
    $focusBody = substr($membersJs, $focusStart, 1200);

    $this->assertStringContainsString('preventScroll: true', $focusBody);
    $this->assertStringContainsString('scrollIntoView({ block: \'nearest\', inline: \'nearest\'', $focusBody);
    $this->assertStringContainsString("behavior: 'auto'", $focusBody);
    $this->assertStringContainsString('options.preventScroll === true', $focusBody);
    $this->assertStringContainsString("options.scroll === 'top'", $focusBody);
    $this->assertStringContainsString("options.scroll === 'bottom'", $focusBody);
  }
}
