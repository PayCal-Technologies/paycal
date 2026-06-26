<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class AriaHiddenFocusContractTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  private function htmlRoot(): string
  {
    return $this->projectRoot() . '/html';
  }

  /**
   * @return list<string>
   */
  private function staticMarkupFiles(): array
  {
    $roots = [
      $this->htmlRoot() . '/business',
      $this->htmlRoot() . '/sites',
      $this->htmlRoot() . '/settings',
      $this->htmlRoot() . '/unverified',
      $this->htmlRoot() . '/auth',
      $this->htmlRoot() . '/header.php',
      $this->htmlRoot() . '/index.php',
    ];

    $files = [];
    foreach ($roots as $root) {
      if (is_file($root)) {
        $files[] = $root;
        continue;
      }

      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
      );
      foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
          continue;
        }
        $path = $fileInfo->getPathname();
        if (!preg_match('/\.php$/', $path)) {
          continue;
        }
        if (str_contains($path, '/tests/')) {
          continue;
        }
        $files[] = $path;
      }
    }

    sort($files);

    return $files;
  }

  private function isFocusableElement(DOMElement $element): bool
  {
    if ($element->hasAttribute('hidden')) {
      return false;
    }

    $tabindex = strtolower(trim($element->getAttribute('tabindex')));
    if ($tabindex === '-1') {
      return false;
    }

    $tag = strtolower($element->tagName);
    if (in_array($tag, ['button', 'select', 'textarea'], true)) {
      return !$element->hasAttribute('disabled');
    }

    if ($tag === 'a') {
      return $element->hasAttribute('href') && trim($element->getAttribute('href')) !== '';
    }

    if ($tag === 'input') {
      $type = strtolower(trim($element->getAttribute('type') ?: 'text'));
      return $type !== 'hidden' && !$element->hasAttribute('disabled');
    }

    if ($element->hasAttribute('tabindex')) {
      return true;
    }

    return false;
  }

  private function hasInertProtection(DOMElement $element): bool
  {
    for ($node = $element; $node instanceof DOMElement; $node = $node->parentNode) {
      if ($node->hasAttribute('hidden')) {
        return true;
      }
      if ($node->hasAttribute('inert')) {
        return true;
      }
    }

    return false;
  }

  /**
   * @return list<string>
   */
  private function findAriaHiddenFocusViolations(string $markup, string $sourceLabel): array
  {
    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $loaded = $document->loadHTML(
      '<?xml encoding="utf-8" ?><div id="paycal-a11y-root">' . $markup . '</div>',
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if ($loaded === false) {
      return [];
    }

    $xpath = new DOMXPath($document);
    $violations = [];
    $hiddenNodes = $xpath->query('//*[@aria-hidden="true"]');
    if ($hiddenNodes === false) {
      return [];
    }

    foreach ($hiddenNodes as $hiddenNode) {
      if (!$hiddenNode instanceof DOMElement) {
        continue;
      }

      $focusableDescendants = $xpath->query(
        './/a[@href] | .//button | .//input | .//select | .//textarea | .//*[@tabindex]',
        $hiddenNode,
      );
      if ($focusableDescendants === false) {
        continue;
      }

      foreach ($focusableDescendants as $descendant) {
        if (!$descendant instanceof DOMElement || $descendant === $hiddenNode) {
          continue;
        }
        if (!$this->isFocusableElement($descendant)) {
          continue;
        }
        if ($this->hasInertProtection($descendant)) {
          continue;
        }

        $violations[] = sprintf(
          '%s: aria-hidden ancestor <%s%s> contains focusable <%s%s>',
          $sourceLabel,
          $hiddenNode->tagName,
          $hiddenNode->hasAttribute('id') ? ' id="' . $hiddenNode->getAttribute('id') . '"' : '',
          $descendant->tagName,
          $descendant->hasAttribute('id') ? ' id="' . $descendant->getAttribute('id') . '"' : '',
        );
      }
    }

    return array_values(array_unique($violations));
  }

  #[Test]
  public function coreExportsInertHiddenStateHelper(): void
  {
    $a11yJs = (string) file_get_contents($this->htmlRoot() . '/js/core/a11y.js');
    $coreJs = (string) file_get_contents($this->htmlRoot() . '/js/core/index.php');

    $this->assertStringContainsString('export function setInertHiddenState', $a11yJs);
    $this->assertStringContainsString('setInertHiddenState,', $a11yJs);
    $this->assertStringContainsString("import A11yModule, { setInertHiddenState }", $coreJs);
    $this->assertStringContainsString('setInertHiddenState: applyInertHiddenState', $coreJs);
  }

  #[Test]
  public function businessMembersBulkToolbarUsesInertHiddenPattern(): void
  {
    $membersPage = (string) file_get_contents($this->htmlRoot() . '/business/members/index.php');
    $membersJs = (string) file_get_contents($this->htmlRoot() . '/js/business/subpages/members.js.php');

    $this->assertStringContainsString('id="business_members_bulk_toolbar"', $membersPage);
    $this->assertStringContainsString('inert', $membersPage);
    $this->assertStringNotContainsString(
      'id="business_members_bulk_toolbar"' . "\n" . '      class="business_members_bulk_toolbar business_members_bulk_toolbar_compact"' . "\n" . '      aria-hidden="true"',
      $membersPage,
    );
    $this->assertStringContainsString('syncInertHiddenState', $membersJs);
    $this->assertStringContainsString('syncInertHiddenState(elements.membersBulkToolbar, !active)', $membersJs);
    $this->assertStringNotContainsString(
      "elements.membersBulkToolbar.setAttribute('aria-hidden', active ? 'false' : 'true')",
      $membersJs,
    );
  }

  #[Test]
  public function visibilityHiddenSurfacesUseInertHiddenStateInJs(): void
  {
    $files = [
      'js/business/subpages/members.js.php' => 'syncInertHiddenState',
      'js/sites/index.php' => 'PC.setInertHiddenState',
      'js/datagrid/index.php' => 'setInertHiddenState',
      'js/calendar/calendar.js' => 'setInertHiddenState',
      'js/business/workspace.js.php' => 'PC.setInertHiddenState',
    ];

    foreach ($files as $relativePath => $needle) {
      $contents = (string) file_get_contents($this->htmlRoot() . '/' . $relativePath);
      $this->assertStringContainsString(
        $needle,
        $contents,
        $relativePath . ' should use ' . $needle . ' for aria-hidden containers with focusable descendants',
      );
    }
  }

  #[Test]
  public function staticMarkupDoesNotHideFocusableDescendantsWithoutInert(): void
  {
    $violations = [];
    foreach ($this->staticMarkupFiles() as $path) {
      $contents = (string) file_get_contents($path);
      $relative = str_replace($this->projectRoot() . '/', '', $path);
      $chunks = preg_split('/<\?php.*?\?>/s', $contents) ?: [];
      $markup = implode('', $chunks);
      if (trim($markup) === '') {
        continue;
      }

      $violations = array_merge(
        $violations,
        $this->findAriaHiddenFocusViolations($markup, $relative),
      );
    }

    $this->assertSame(
      [],
      $violations,
      "Found aria-hidden containers with focusable descendants that lack inert/hidden protection:\n" . implode("\n", $violations),
    );
  }
}
